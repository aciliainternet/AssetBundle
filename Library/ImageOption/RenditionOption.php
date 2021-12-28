<?php

namespace Acilia\Bundle\AssetBundle\Library\ImageOption;

class RenditionOption extends AbstractOption
{
    public function __construct(array $options, $entity, string $type, $ratios, $renditions)
    {
        parent::__construct($options, $entity, $type);

        $mandatoryOptions = ['title', 'renditions', 'attribute'];

        foreach ($mandatoryOptions as $option) {
            if (!isset($options[$option])) {
                throw new \Exception(sprintf('The option "%s" was not found.', $option));
            }
        }

        $this->renditions = $this->calculateRenditions($options['renditions'], $renditions);
        $this->aspectRatios = $this->calculateAspectRatios($ratios);

        // Calculate Min Sizes for each Aspect Ratio
        foreach ($this->aspectRatios as $aspectRatio => $sizes) {
            $minWidth = 0;
            $minHeight = 0;

            foreach ($sizes as $size) {
                list($width, $height) = explode('x', $size);

                // Min Width
                if ($width > $minWidth) {
                    $minWidth = $width;
                }

                // Min Height
                if ($height > $minHeight) {
                    $minHeight = $height;
                }
            }

            $this->minWidths[$aspectRatio] = $minWidth;
            $this->minHeights[$aspectRatio] = $minHeight;
        }
    }

    protected function calculateRenditions($renditions, $renditionsAliases)
    {
        $imageRenditions = [];

        foreach ($renditions as $renditionName) {
            $imageRenditions[$renditionName] = $renditionsAliases[$renditionName];
        }

        return $imageRenditions;
    }

    protected function calculateAspectRatios(array $ratios): array
    {
        $aspectRatios = [];

        foreach ($this->renditions as $renditionName => $renditionSizes) {
            foreach ($renditionSizes as $sizeName => $size) {
                list($width, $height) = explode('x', $size, 2);
                $sizeRatio = (integer) (($width / $height) * 100);

                if (isset($ratios[$sizeRatio])) {
                    $aspectRatios[$ratios[$sizeRatio]][] = $size;
                }
            }
        }

        return $aspectRatios;
    }

    public function getRenditions(): array
    {
        return $this->renditions;
    }

    public function getRendition(?string $rendition): array
    {
        if (isset($this->renditions[$rendition])) {
            return $this->renditions[$rendition];
        }

        throw new \Exception(sprintf(
            'The rendition "%s" is not assigned for the entity "%s".',
            $rendition,
            $this->entity
        ));
    }

    public function getFirstSize(): ?string
    {
        foreach ($this->renditions as $sizes) {
            foreach ($sizes as $size) {
                return $size;
            }
        }

        return null;
    }

    public function getAspectRatios(string $replace = 'x'): array
    {
        $aspectRatios = [];

        foreach (array_keys($this->aspectRatios) as $aspectRatio) {
            $aspectRatios[] = str_replace('x', $replace, $aspectRatio);
        }

        return $aspectRatios;
    }

    public function getSpecs(): array
    {
        $specs = [];

        foreach (array_keys($this->aspectRatios) as $aspectRatio) {
            $specs[$aspectRatio] = [
                'aspectRatio' => round($this->minWidths[$aspectRatio] / $this->minHeights[$aspectRatio], 3),
                'minWidth' => $this->minWidths[$aspectRatio],
                'minHeight' => $this->minHeights[$aspectRatio],
            ];
        }

        return $specs;
    }

    public function getMinHeight(string $rendition): int
    {
        if ($this->retina) {
            return $this->minHeights[$rendition] * 2;
        }

        return $this->minHeights[$rendition];
    }

    public function getFinalRenditions(string $aspectRatio): array
    {
        $sizes = array_unique($this->aspectRatios[$aspectRatio]);
        $renditions = [];

        foreach ($sizes as $size) {
            list($width, $height) = explode('x', $size);
            $renditions[] = ['w' => $width, 'h' => $height, 'n' => $size];
            if ($this->retina) {
                $renditions[] = ['w' => $width * 2, 'h' => $height * 2, 'n' => $size . '@2x'];
            }
        }

        return $renditions;
    }
}
