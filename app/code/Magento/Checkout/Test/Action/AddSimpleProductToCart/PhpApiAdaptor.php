<?php

namespace Magento\Checkout\Test\Action\AddSimpleProductToCart;

class PhpApiAdaptor implements PhpApiTestActionAdaptorInterface
{
    /**
     * Input should be injected from the adaptor-agnostic scenario config.
     *
     * @param array $input
     * @param array $context
     */
    public function execute($input, $context)
    {
        // Add simple prouct to the cart using PHP API marked with @api

        // Implement modification restriction on the previous level
        return ['item_id' => 234];
    }
}
