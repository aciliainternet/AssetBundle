<?php

namespace Acilia\Bundle\AssetBundle\Library\ImageOption;

use Exception;

class CustomOption extends AbstractOption
{
    protected $thumbWidth = 150;

    public function __construct($options, $entity, $type)
    {
        parent::__construct($options, $entity, $type);

        $mandatoryOptions = ['title', 'attribute'];

        foreach ($mandatoryOptions as $option) {
            if (!isset($options[$option])) {
                throw new Exception(sprintf('The option "%s" was not found.', $option));
            }
        }

        // retina is disabled for custom sizes
        $this->retina = false;
        $this->minWidths = 0;
        $this->minHeights = 0;
        $this->preserveOriginal = false;

        if (isset($options['restrictions'])) {
            if (isset($options['restrictions']['min-width'])) {
                $this->minWidths = $options['restrictions']['min-width'];
            }
            if (isset($options['restrictions']['min-height'])) {
                $this->minHeights = $options['restrictions']['min-height'];
            }
            if (isset($options['restrictions']['thumb-width'])) {
                $this->thumbWidth = $options['restrictions']['thumb-width'];
            }
        }

        if (isset($options['preserveOriginal'])) {
            $this->preserveOriginal = $options['preserveOriginal'];
        }
    }

    public function getSpecs()
    {
        $specs['custom'] = [
            'aspectRatio' => false,
            'minWidth' => $this->minWidths,
            'minHeight' => $this->minHeights
        ];

        return $specs;
    }

    public function getRendition($rendition)
    {
        return 'custom';
    }

    public function getFirstSize()
    {
        return 'original.custom';
    }

    public function getAspectRatios($replace = 'x')
    {
        $aspectRatios = ['custom'];

        return $aspectRatios;
    }

    public function getMinHeight($rendition)
    {
        return $this->minHeights;
    }

    public function getFinalRenditions($aspectRatio)
    {
        $renditions[] = ['w' => $this->thumbWidth, 'h' => null, 'n' => 'thumb.custom'];

        return $renditions;
    }
}
