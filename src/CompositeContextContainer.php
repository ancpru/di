<?php


namespace yii\di;

use phpDocumentor\Reflection\Types\Context;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use yii\di\exceptions\NotFoundException;

/**
 * This class implements a composite container with support for context switching.
 * The intended use is to allow for custom configurations of (nested) modules.
 */
class CompositeContextContainer implements ContainerInterface
{
    /**
     * We use a simple array since order matters
     * The first level of this array is sorted by the length of the key, from long to short.
     * @var ContainerInterface[string][int] The list of containers
     */
    private $containers = [];

    private $currentContext = '';

    /** @inheritdoc */
    public function get($id)
    {
        foreach ($this->getContainers($this->currentContext) as $container) {
            try {
                return $container->get($id);
            } catch (NotFoundExceptionInterface $e) {
                continue;
            }
        }
        throw new NotFoundException();
    }

    /**
     * @return ContainerInterface[] All containers in the current context
     */
    private function getContainers(string $context): iterable
    {
        foreach ($this->containers as $prefix => $containers) {
            if (strncmp($prefix, $context, mb_strlen($prefix)) !== 0) {
                continue;
            }
            foreach ($containers as $container) {
                yield $container;
            }
        }
    }

    /** @inheritdoc */
    public function has($id)
    {
        foreach ($this->getContainers($this->currentContext) as $container) {
            if ($container->has($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Attaches a container to the composite container.
     * @param string The context for the new container.
     * @param ContainerInterface $container
     */
    public function attach(ContainerInterface $container, string $context = '')
    {
        if (isset($this->containers[$context])) {
            array_unshift($this->containers[$context], $container);
        } else {
            // If the context is new we reorder the containers array.
            $this->containers[$context] = [
                $container
            ];
            uksort($this->containers, function ($a, $b) {
                return mb_strlen($b) <=> mb_strlen($a);
            });
        }
    }

    /**
     * Removes a container from the list of containers.
     * @param ContainerInterface $container
     */
    public function detach(ContainerInterface $container)
    {
        foreach ($this->containers as $prefix => $containers) {
            foreach ($containers as $i => $c) {
                if ($container === $c) {
                    unset($this->containers[$prefix][$i]);
                }
            }
            if (!empty($this->containers[$prefix])) {
                $this->containers[$prefix] = array_values($this->containers[$prefix]);
            } else {
                unset($this->containers[$prefix]);
            }
        }
    }

    /**
     * Gets a service from the container in the context.
     *
     * @param string $id Name of the service, not typehinted to remain compatible with PSR-11 `get()`
     * @param string $context
     */
    public function getFromContext($id, string $context)
    {
        foreach ($this->getContainers($context) as $container) {
            try {
                return $container->get($id);
            } catch (NotFoundExceptionInterface $e) {
                continue;
            }
        }
        throw new NotFoundException();
    }

    /**
     * Checks if we have a definition for a service in the given context
     * @param string $id Name of the service, not typehinted to remain compatible with PSR-11 `has()`
     * @param string $context The context to use
     * @return bool
     */
    public function hasInContext($id, string $context): bool
    {
        foreach ($this->getContainers($context) as $container) {
            if ($container->has($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * This will return a container that only resolves services from a specific context.
     * @param string $context
     * @return ContainerInterface
     */
    public function getContextContainer(string $context): ContainerInterface
    {
        return new ContextContainer($this, $context);
    }
}