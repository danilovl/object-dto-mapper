<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Tests\Mock\Security;

use Danilovl\ObjectDtoMapper\Attribute\Map;
use stdClass;

final class TransformedObjectDto
{
    public function __construct(
        #[Map(transform: static function (mixed $value): stdClass {
            $result = new stdClass;
            $result->wrapped = true;

            return $result;
        })]
        public object $value
    ) {}
}
