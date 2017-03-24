<?php

namespace MediaMonks\SonataMediaBundle\Generator;

use League\Glide\Filesystem\FilesystemException;
use League\Glide\Server;
use MediaMonks\SonataMediaBundle\Model\MediaInterface;

class ImageGenerator
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var FilenameGeneratorInterface
     */
    private $filenameGenerator;

    /**
     * @var string
     */
    private $tmpPath;

    /**
     * @var string
     */
    private $tmpPrefix;

    /**
     * @var string
     */
    private $fallbackImage;

    /**
     * @param Server $server
     * @param FilenameGeneratorInterface $filenameGenerator
     * @param null $fallbackImage
     * @param null $tmpPath
     * @param null $tmpPrefix
     */
    public function __construct(
        Server $server,
        FilenameGeneratorInterface $filenameGenerator,
        $fallbackImage = null,
        $tmpPath = null,
        $tmpPrefix = null
    ) {
        $this->server = $server;
        $this->filenameGenerator = $filenameGenerator;
        $this->fallbackImage = $fallbackImage;
        $this->tmpPath = $tmpPath;
        $this->tmpPrefix = $tmpPrefix;
    }

    /**
     * @param MediaInterface $media
     * @param array $parameters
     * @return string
     * @throws FilesystemException
     */
    public function generate(MediaInterface $media, array $parameters)
    {
        $filename = $this->filenameGenerator->generate($media, $parameters);

        if (!$this->server->getSource()->has($filename)) {
            $this->generateImage($media, $parameters, $filename);
        }

        return $filename;
    }

    /**
     * @param MediaInterface $media
     * @param array $parameters
     * @param $filename
     * @throws FilesystemException
     * @throws \Exception
     */
    private function generateImage(MediaInterface $media, array $parameters, $filename)
    {
        $tmp = $this->getTemporaryFile();
        if ($this->server->getSource()->has($media->getImage())) {
            $imageData = $this->server->getSource()->read($media->getImage());
        }
        else {
            if (!is_null($this->fallbackImage)) {
                $imageData = file_get_contents($this->fallbackImage);
            }
            else {
                throw new FilesystemException('File not found');
            }
        }

        if (@file_put_contents($tmp, $imageData) === false) {
            throw new FilesystemException('Unable to write temporary file');
        }
        try {
            $this->server->getCache()->write($filename, $this->server->getApi()->run($tmp, $parameters));
        } catch (\Exception $e) {
            throw new \Exception('Could not generate image', 0, $e);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * @return string
     */
    private function getTemporaryFile()
    {
        if (empty($this->tmpPath)) {
            $this->tmpPath = sys_get_temp_dir();
        }
        if (empty($this->tmpPrefix)) {
            $this->tmpPrefix = 'media';
        }

        return tempnam($this->tmpPath, $this->tmpPrefix);
    }
}
