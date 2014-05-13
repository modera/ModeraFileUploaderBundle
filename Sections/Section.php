<?php

namespace Modera\MjrIntegrationBundle\Sections;

/**
 * A default immutable implementation.
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2013 Modera Foundation
 */
class Section implements SectionInterface
{
    private $id;
    private $controller;
    private $metadata;

    /**
     * @param string $id
     * @param string $controller
     * @param array  $metadata
     */
    public function __construct($id, $controller, array $metadata = array())
    {
        $this->id = $id;
        $this->controller = $controller;
        $this->metadata = $metadata;
    }

    /**
     * @inheritDoc
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}