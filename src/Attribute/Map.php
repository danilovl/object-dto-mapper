<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Attribute;

use Attribute;
use Closure;
use Danilovl\ObjectDtoMapper\Exception\{
    MappingIfException,
    MappingTransformException
};

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
readonly class Map
{
    /**
     * @param class-string|null $target
     */
    public function __construct(
        public ?string $target = null,
        public ?string $source = null,
        public mixed $if = null,
        public ?string $ifContainer = null,
        public mixed $transform = null,
        public ?string $transformContainer = null
    ) {
        if ($this->if !== null && $this->ifContainer !== null) {
            throw new MappingIfException('Map(if) configuration invalid: cannot set both if and ifContainer at the same time');
        }

        if ($this->transform !== null && $this->transformContainer !== null) {
            throw new MappingTransformException('Map(transform) configuration invalid: cannot set both transform and transformContainer at the same time');
        }

        if ($this->ifContainer !== null && mb_trim($this->ifContainer) === '') {
            throw new MappingIfException('Map(if) container definition must be a non-empty string');
        }

        if ($this->transformContainer !== null && mb_trim($this->transformContainer) === '') {
            throw new MappingTransformException('Map(transformContainer) container definition must be a non-empty string');
        }

        if ($this->if !== null && !is_bool($this->if) && !is_string($this->if) && !is_array($this->if) && !$this->if instanceof Closure) {
            throw new MappingIfException(sprintf('Map(if) definition must be bool, string, array or Closure, %s given', get_debug_type($this->if)));
        }

        if ($this->transform !== null && !is_string($this->transform) && !is_array($this->transform) && !$this->transform instanceof Closure) {
            throw new MappingTransformException(sprintf('Map(transform) definition must be string, array or Closure, %s given', get_debug_type($this->transform)));
        }
    }
}
