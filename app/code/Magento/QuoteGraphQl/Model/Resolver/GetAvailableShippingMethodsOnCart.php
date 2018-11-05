<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Resolver;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Shipping\Model\Config as ShippingConfig;

/**
 * @inheritdoc
 */
class GetAvailableShippingMethodsOnCart implements ResolverInterface
{
    /**
     * @var ShippingConfig
     */
    private $shippingConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var ShipmentEstimationInterface
     */
    private $shipmentEstimation;

    /**
     * GetAvailableShippingMethodsOnCart constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ShippingConfig $shippingConfig
     * @param AddressInterfaceFactory $addressFactory
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param ShipmentEstimationInterface $shipmentEstimation
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ShippingConfig $shippingConfig,
        AddressInterfaceFactory $addressFactory,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        ShipmentEstimationInterface $shipmentEstimation
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->scopeConfig = $scopeConfig;
        $this->addressFactory = $addressFactory;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->shipmentEstimation = $shipmentEstimation;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $shippingMethods = [];

        if ($value['country_id']) {
            $cartId = $this->maskedQuoteIdToQuoteId->execute($value['cart_id']);
            /** @var AddressInterface $addressFactory */
            $addressFactory = $this->addressFactory->create();
            $addressFactory->setCountryId($value['country_id']);
            if ($value['postcode']) {
                $addressFactory->setPostcode($value['postcode']);
            }
            if ($value['region_id']) {
                $addressFactory->setRegionId($value['region_id']);
            }
            $shippingMethods = $this->shipmentEstimation->estimateByExtendedAddress($cartId, $addressFactory);
        }
        $data = [];

        foreach ($shippingMethods as $shippingMethod) {
            $data[] = [
                'code' => $shippingMethod->getCarrierCode(),
                'label' => $shippingMethod->getCarrierTitle(),
                'error_message' => $shippingMethod->getErrorMessage() ?: '',
                'free_shipping' => $shippingMethod->getAmount() == 0,
                'is_available' => $shippingMethod->getAvailable()
            ];
        }

        return $data;
    }
}
