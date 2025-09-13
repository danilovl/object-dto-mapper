<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Attribute;

use Attribute;
use Danilovl\ObjectDtoMapper\Exception\{
    MappingIfException,
    MappingTransformException
};

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
readonly class Map
{
    /**
     * @param string|class-string|null $target The class to map to
     * @param string|class-string|null $source The property o map from
     * @param string|bool|callable(mixed, object): bool|null $if A boolean or a callable that instructs whether to map
     * @param string|null $ifContainer A service id that instructs whether to map
     * @param (string|callable(mixed, object): mixed)|null $transform A callable that transforms the value during mapping
     * @param string|null $transformContainer A service id from container that transforms the value during mapping
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

        if ($ifContainer !== null && mb_trim($ifContainer) === '') {
            $message = 'Map(if) container definition must be a non-empty string';

            throw new MappingIfException($message);
        }

        if ($transformContainer !== null && mb_trim($transformContainer) === '') {
            $message = 'Map(transformContainer) container definition must be a non-empty string';

            throw new MappingTransformException($message);
        }
    }
}
