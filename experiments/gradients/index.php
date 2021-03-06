<?php

require __DIR__.'/../../vendor/autoload.php';
ini_set('max_execution_time', 60);

use \Carica\CanvasGraphics;
use \Carica\CanvasGraphics\SVG;
use \Carica\CanvasGraphics\Vectorizer;

if (
  isset($_FILES['bitmap']['tmp_name']) &&
  is_uploaded_file($_FILES['bitmap']['tmp_name'])
) {

  $path = __DIR__.'/images';
  $id = md5(uniqid('', TRUE));
  [,,$bitmapType] = getimagesize($_FILES['bitmap']['tmp_name']);
  $bitmapFile = $path.'/'.$id.image_type_to_extension($bitmapType);
  @mkdir($path, 0777, TRUE);
  if (
    (
      $bitmapType === IMAGETYPE_JPEG || $bitmapType = IMAGETYPE_PNG
    ) &&
    is_dir($path) &&
    move_uploaded_file($_FILES['bitmap']['tmp_name'], $bitmapFile)
  ) {
    // start converting
    $image = CanvasGraphics\Canvas\GD\Image::load($bitmapFile);
    if ($image) {
      $start = microtime(TRUE);

      $image->filter(
        new CanvasGraphics\Canvas\GD\Filter\LimitSize(120, 120),
        new CanvasGraphics\Canvas\GD\Filter\Blur(4)
      );
      $context = $image->getContext('2d');
      $imageData = $context->getImageData();
      $gradients = new Vectorizer\Gradients(
        $imageData,
        CanvasGraphics\Color\PaletteFactory::createPalette(
          CanvasGraphics\Color\PaletteFactory::PALETTE_COLOR_THIEF,
          $imageData,
          16
        )
      );
      $svg = new SVG\Document(
        60,
        60,
        [
          SVG\Document::OPTION_BLUR => 0,
          SVG\Document::OPTION_FORMAT_OUTPUT => FALSE
        ]
      );

      $svg->append($gradients);
      $xml = $svg->getXML();
      file_put_contents($path.'/'.$id.'.svg', $xml);

      $gradientsStyle = $gradients->getStyleProperty();

      $timeNeeded = microtime(TRUE) - $start;

      $sizeBitmap = filesize($bitmapFile);
      $sizeSvg = filesize($path.'/'.$id.'.svg');
      $sizeFactor = $sizeSvg / $sizeBitmap * 100;
    }
  }
}

function bytesToString($bytes, $decimals = 2, $decimalSeparator = '.') {
  $exponents = [
    'GB' => 3, 'MB' => 2, 'kB' => 1, 'B' => 0,
  ];
  $unit = 'B';
  $size = $bytes;
  foreach ($exponents as $unit => $exponent) {
    if ($exponent > 0) {
      $factor = 1024 ** $exponent;
      if ($bytes > $factor) {
        $size = $bytes / $factor;
        break;
      }
    } else {
      return round($bytes).' '.$unit;
    }
  }
  return number_format($size, $decimals, $decimalSeparator, '').' '.$unit;
}


$values = [
  'bitmap' => isset($bitmapFile) ? htmlspecialchars('images/'.basename($bitmapFile)): '',
  'svg_xml' => isset($svg) ? htmlspecialchars($xml) : '',
  'svg_data' => isset($svg) ? htmlspecialchars('data:image/svg+xml;base64,'.base64_encode($xml)) : '',
  'gradients' => isset($gradientsStyle) ? htmlspecialchars('background:'.$gradientsStyle) : '',
  'size_bitmap' => isset($bitmapFile) ? bytesToString($sizeBitmap, 0) : '',
  'size_svg' => isset($bitmapFile) ? bytesToString($sizeSvg) : '',
  'size_factor' => isset($bitmapFile) ? number_format($sizeFactor) : '',
  'time_needed' => isset($timeNeeded) ? number_format($timeNeeded, 4) : ''
]

?>
<html>
  <head>
    <title>Upload + Convert</title>
    <style type="text/css">
      body {
        background-color: black;
        color: white;
      }
      form {
        text-align: center;
      }
      form ul {
        list-style: none;
        display: inline-block;
      }
      form ul li {
        display: inline-block;
      }
      .images {
        display: flex;
      }
      .images img, .images .rect {
        width: 300px;
        margin: auto;
        border: 1px solid rgba(255, 255, 255, 0.5);
      }
      .images .svg,
      .images .rect {
        width: 300px;
        height: 200px;
      }
      textarea {
        width: 100%;
        height: 600px;
      }
      .xml {
        margin: 5px;
        padding: 5px;
        border: 1px solid black;
        white-space: pre-wrap;
        font-family: "Fira Code Retina", monospace;
        font-size: 8px;
      }
    </style>
  </head>
  <body>
    <form action="index.php" method="post" enctype="multipart/form-data">
      <label>Bitmap file:</label>
      <input type="file" name="bitmap">
      <button type="submit">Upload</button>
      <ul>
        <li>Bitmap: <?=$values['size_bitmap']?></li>
        <li>SVG: <?=$values['size_svg']?> (<?=$values['size_factor']?>%) </li>
        <li>Time: <?=$values['time_needed']?>s </li>
      </ul>
    </form>
    <section class="images">
      <img src="<?=$values['bitmap']?>"/>
      <img class="svg" src="<?=$values['svg_data']?>"/>
      <div class="rect" style="<?=$values['gradients']?>">&nbsp;</div>
    </section>
    <section class="xml"><?=$values['svg_xml']?></section>
  </body>
</html>
