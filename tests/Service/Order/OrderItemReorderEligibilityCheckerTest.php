<?php
declare(strict_types=1);

namespace App\Tests\Service\Order;

use App\Exception\ReorderException;
use App\Service\Order\OrderItemReorderEligibilityChecker;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Inventory\Checker\OrderItemAvailabilityCheckerInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class OrderItemReorderEligibilityCheckerTest extends TestCase
{
    private Security $security;
    private OrderItemAvailabilityCheckerInterface $availabilityChecker;
    private OrderItemReorderEligibilityChecker $eligibilityChecker;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->availabilityChecker = $this->createMock(OrderItemAvailabilityCheckerInterface::class);

        $this->eligibilityChecker = new OrderItemReorderEligibilityChecker(
            $this->security,
            $this->availabilityChecker
        );
    }

    public function testValidateFailsWhenVariantIsNull(): void
    {
        $orderItem = $this->createMock(OrderItemInterface::class);
        $user = $this->createMock(UserInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $order = $this->createMock(OrderInterface::class);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $orderItem->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);

        $order->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $customer->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->availabilityChecker->expects($this->once())
            ->method('isReservedStockSufficient')
            ->with($orderItem)
            ->willReturn(true);

        $orderItem->expects($this->once())
            ->method('getVariant')
            ->willReturn(null);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Product variant not available.');

        $this->eligibilityChecker->validate($orderItem);
    }

    public function testValidateFailsWithInvalidQuantity(): void
    {
        $orderItem = $this->createMock(OrderItemInterface::class);
        $user = $this->createMock(UserInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $orderItem->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);

        $order->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);

        $customer->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->availabilityChecker->expects($this->once())
            ->method('isReservedStockSufficient')
            ->with($orderItem)
            ->willReturn(true);

        $orderItem->expects($this->once())
            ->method('getVariant')
            ->willReturn($variant);

        $orderItem->expects($this->once())
            ->method('getQuantity')
            ->willReturn(0);

        $this->expectException(ReorderException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0.');

        $this->eligibilityChecker->validate($orderItem);
    }
}
