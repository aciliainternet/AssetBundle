<?php

namespace Acilia\Bundle\AssetBundle\Library\ImageOption;

class CustomOption extends AbstractOption
{
    protected $thumbWidth = 150;

    public function __construct(array $options, $entity, string $type)
    {
        parent::__construct($options, $entity, $type);

        $mandatoryOptions = ['title', 'attribute'];

        foreach ($mandatoryOptions as $option) {
            if (!isset($options[$option])) {
                throw new \Exception(sprintf('The option "%s" was not found.', $option));
            }
        }

        // retina is disabled for custom sizes
        $this->retina = false;
        $this->minWidths = 0;
        $this->minHeights = 0;

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
    }

    public function getSpecs(): array
    {
        $specs['custom'] = [
            'aspectRatio' => false,
            'minWidth' => $this->minWidths,
            'minHeight' => $this->minHeights
        ];

        return $specs;
    }

    public function getRendition(?string $rendition): array
    {
        return ['custom'];
    }

    public function getFirstSize(): ?string
    {
        return 'original.custom';
    }

    public function getAspectRatios(string $replace = 'x'): array
    {
        $aspectRatios = ['custom'];

        return $aspectRatios;
    }

    public function getMinHeight(string $rendition): int
    {
        return $this->minHeights;
    }

    public function getFinalRenditions(string $aspectRatio): array
    {
        $renditions[] = [
            'w' => $this->thumbWidth,
            'h' => null,
            'n' => 'thumb.custom'
        ];

        return $renditions;
    }
}
