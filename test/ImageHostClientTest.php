<?php

namespace AutowpImageHostClientTest\Controller;

use PHPUnit\Framework\TestCase;

use Autowp\ImageHostClient\ImageHostClient;

class AutowpImageHostClientTest extends TestCase
{
    protected $applicationConfigPath = __DIR__ . '/../config/application.config.php';

    public function testAddImageFromFile()
    {
        $client = new ImageHostClient('localhost', 80);

        $client->addImageFromFile(__DIR__ . '/_files/image.jpg', 'foo');

        $this->assertTrue(true);
    }

    public function testAddImageFromBlob()
    {
        $client = new ImageHostClient('localhost', 80);

        $client->addImageFromBlob(file_get_contents(__DIR__ . '/_files/image.jpg'), 'foo');

        $this->assertTrue(true);
    }

    public function testAddImageFromImagick()
    {
        $client = new ImageHostClient('localhost', 80);

        $imagick = new \Imagick();
        $imagick->readimage(__DIR__ . '/_files/image.jpg');

        $client->addImageFromImagick($imagick, 'foo');

        $this->assertTrue(true);
    }

    public function testGetImage()
    {
        $client = new ImageHostClient('localhost', 80);

        $imageId = $client->addImageFromFile(__DIR__ . '/_files/image.jpg', 'foo');

        $image = $client->getImage($imageId);

        $this->assertNotEmpty($image);

        $this->assertEquals(1920, $image->getWidth());
        $this->assertEquals(1345, $image->getHeight());
        $this->assertNotEmpty($image->getSrc());
    }

    public function testGetImageBlob()
    {
        $client = new ImageHostClient('localhost', 80);

        $imageId = $client->addImageFromFile(__DIR__ . '/_files/image.jpg', 'foo');

        $blob = $client->getImageBlob($imageId);

        $this->assertEquals(file_get_contents(__DIR__ . '/_files/image.jpg'), $blob);
    }
}
