<?php

namespace Overdose\MagentoOptimizer\Model\Config\Backend;

/**
 * @deprecated and was replaced by Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
 */
class ExcludeControllers extends \Magento\Framework\App\Config\Value
{
    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * ExcludeControllers constructor.
     *
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Prepare data before save
     *
     * @return void
     */
    public function beforeSave()
    {
        /** @var array $value */
        $value = $this->getValue();
        unset($value['__empty']);
        $encodedValue = $this->serializer->serialize($value);

        $this->setValue($encodedValue);
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        /** @var string $value */
        $value = $this->getValue();
        $decodedValue = $this->serializer->unserialize($value);

        $this->setValue($decodedValue);
    }
}
