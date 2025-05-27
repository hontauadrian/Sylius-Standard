<?php
declare(strict_types=1);

namespace App\Service\Order;

use App\Exception\ReorderException;
use Sylius\Component\Core\Inventory\Checker\OrderItemAvailabilityCheckerInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class OrderItemReorderEligibilityChecker implements OrderItemReorderEligibilityCheckerInterface
{
    public function __construct(
        private Security                              $security,
        private OrderItemAvailabilityCheckerInterface $availabilityChecker,
    )
    {
    }

    public function validate(OrderItemInterface $orderItem): void
    {
        $user = $this->security->getUser();
        $customer = $orderItem->getOrder()?->getCustomer();

        if (!$customer || $customer->getUser() !== $user) {
            throw new ReorderException('You are not allowed to reorder this item.');
        }

        if (!$this->availabilityChecker->isReservedStockSufficient($orderItem)) {
            throw new ReorderException('The requested quantity is no longer available.');
        }

        if (!$orderItem->getVariant()) {
            throw new ReorderException('Product variant not available.');
        }

        if ($orderItem->getQuantity() <= 0) {
            throw new ReorderException('Quantity must be greater than 0.');
        }
    }
}
