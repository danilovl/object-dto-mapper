<?php declare(strict_types=1);

namespace Danilovl\ObjectDtoMapper\Service;

use Danilovl\ObjectDtoMapper\Exception\{
    CircularReferenceException,
    MaxDepthException
};

final class MappingContext
{
    public const string FILTER_ALLOW = 'allowGroups';

    public const string FILTER_IGNORE = 'ignoreGroups';

    /**
     * @var array<string, true>
     */
    private array $active = [];

    /**
     * @var list<string>
     */
    private array $path = [];

    private int $depth = 0;

    /**
     * @param string[] $allowGroups
     * @param string[] $ignoreGroups
     */
    public function __construct(
        public readonly array $allowGroups,
        public readonly array $ignoreGroups,
        private readonly int $maxDepth
    ) {}

    /**
     * @param string[] $propertyGroups
     * @return self::FILTER_*|null
     */
    public function getGroupFilterViolation(array $propertyGroups): ?string
    {
        if (count($this->ignoreGroups) > 0 && count(array_intersect($this->ignoreGroups, $propertyGroups)) > 0) {
            return self::FILTER_IGNORE;
        }

        if (count($this->allowGroups) > 0 && count(array_intersect($this->allowGroups, $propertyGroups)) === 0) {
            return self::FILTER_ALLOW;
        }

        return null;
    }

    public function enter(object $source, string $target): void
    {
        if ($this->depth >= $this->maxDepth) {
            $message = sprintf('Maximum mapping depth of %d exceeded while mapping "%s" to "%s"%s', $this->maxDepth, $source::class, $target, $this->describePath());

            throw new MaxDepthException($message);
        }

        $key = $this->key($source, $target);
        if (isset($this->active[$key])) {
            $message = sprintf(
                'Circular reference detected: object of class "%s" is already being mapped to "%s"%s. Break the cycle with groups, Map(if) or a separate DTO',
                $source::class,
                $target,
                $this->describePath()
            );

            throw new CircularReferenceException($message);
        }

        $this->active[$key] = true;
        $this->depth++;
    }

    public function leave(object $source, string $target): void
    {
        unset($this->active[$this->key($source, $target)]);
        $this->depth--;
    }

    public function pushPath(string $segment): void
    {
        $this->path[] = $segment;
    }

    public function popPath(): void
    {
        array_pop($this->path);
    }

    public function getPath(): string
    {
        $result = '';
        foreach ($this->path as $segment) {
            $result .= ($result === '' || str_starts_with($segment, '[')) ? $segment : '.' . $segment;
        }

        return $result;
    }

    public function describePath(): string
    {
        $path = $this->getPath();

        return $path === '' ? '' : sprintf(' (path: "%s")', $path);
    }

    private function key(object $source, string $target): string
    {
        return spl_object_id($source) . '>' . $target;
    }
}
