<?php

use App\Http\Controllers\ImageController;

class ImageControllerUnitTest extends TestCase
{
    private $controller;

    public function setUp()
    {
        $this->controller = new ImageController();
        parent::setUp();
    }

    public function tearDown()
    {
        $this->controller = null;
        parent::tearDown();
    }

    /**
     * @covers App\Http\Controllers\ImageController::getCurrentImage
     * @covers App\Http\Controllers\ImageController::_construct
     */
    public function testGetCurrentDefaultImage()
    {
        $imagePath = $this->controller->getCurrentImage();
        $this->assertEquals("public/assets/default-wine-image.png", $imagePath);
    }
}