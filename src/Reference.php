<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use yii\di\exceptions\InvalidConfigException;

/**
 * Reference points to another container definition by its ID
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @since 1.0
 */
class Reference
{
    /**
     * @var string the component ID, class name, interface name or alias name
     */
    public $id;


    /**
     * Constructor.
     * @param string $id the component ID
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Creates reference to given ID.
     *
     * @param string $id
     * @return Reference
     */
    public static function to($id): self
    {
        return new self($id);
    }

    /**
     * Restores class state after using `var_export()`.
     *
     * @param array $state
     * @return Reference
     * @throws InvalidConfigException when $state property does not contain `id` parameter
     * @see var_export()
     */
    public static function __set_state($state)
    {
        if (!isset($state['id'])) {
            throw new InvalidConfigException(
                'Failed to instantiate class "Instance". Required parameter "id" is missing'
            );
        }

        return new self($state['id']);
    }

    /**
     * @return string
     * TODO: think of disallowing return null see `AbstractContainer::getDependencies()`
     */
    public function getId(): ?string
    {
        return $this->id;
    }
}
