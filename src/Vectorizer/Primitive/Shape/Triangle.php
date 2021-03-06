<?php
namespace Carica\CanvasGraphics\Vectorizer\Primitive\Shape {

  use Carica\CanvasGraphics\Color;

  class Triangle extends Polygon {

    public function __construct(int $width, int $height, Color $backgroundColor) {
      parent::__construct($width, $height, $backgroundColor, 3);
    }
  }
}


