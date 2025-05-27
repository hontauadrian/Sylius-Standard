<?php
declare(strict_types=1);

namespace App\Tests\Service\Order;

use App\Exception\ReorderException;
use App\Service\Order\OrderItemReorder;
use App\Service\Order\OrderItemReorderEligibilityCheckerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class OrderItemReorderTest extends TestCase
{
    private CartContextInterface $cartContext;
    private OrderItemQuantityModifierInterface $quantityModifier;
    private OrderModifierInterface $orderModifier;
    private OrderProcessorInterface $orderProcessor;
    private OrderItemReorderEligibilityCheckerInterface $eligibilityChecker;
    private FactoryInterface $orderItemFactory;
    private OrderRepositoryInterface $orderRepository;
    private OrderItemReorder $reorderService;

    protected function setUp(): void
    {
        $this->cartContext = $this->createMock(CartContextInterface::class);
        $this->quantityModifier = $this->createMock(OrderItemQuantityModifierInterface::class);
        $this->orderModifier = $this->createMock(OrderModifierInterface::class);
        $this->orderProcessor = $this->createMock(OrderProcessorInterface::class);
        $this->eligibilityChecker = $this->createMock(OrderItemReorderEligibilityCheckerInterface::class);
        $this->orderItemFactory = $this->createMock(FactoryInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);

        $this->reorderService = new OrderItemReorder(
            $this->cartContext,
            $this->quantityModifier,
            $this->orderModifier,
            $this->orderProcessor,
            $this->eligibilityChecker,
            $this->orderItemFactory,
            $this->orderRepository
        );
    }

    public function testReorderSuccessWithNewItem(): void
    {
        $orderItem = $this->createMock(OrderItemInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);
        $order = $this->createMock(OrderInterface::class);
        $newItem = $this->createMock(OrderItemInterface::class);
        
        $this->eligibilityChecker->expects($this->once())
            ->method('validate')
            ->with($orderItem);

        $orderItem->expects($this->any())
            ->method('getVariant')
            ->willReturn($variant);

        $orderItem->expects($this->any())
            ->method('getQuantity')
            ->willReturn(2);

        $this->cartContext->expects($this->once())
            ->method('getCart')
            ->willReturn($order);

        $order->expects($this->once())
            ->method('getItems')
            ->willReturn(new ArrayCollection([]));

        $this->orderItemFactory->expects($this->once())
            ->method('createNew')
            ->willReturn($newItem);

        $newItem->expects($this->once())
            ->method('setVariant')
            ->with($variant);

        $this->quantityModifier->expects($this->once())
            ->method('modify')
            ->with($newItem, 2);

        $this->orderModifier->expects($this->once())
            ->method('addToOrder')
            ->with($order, $newItem);

        $this->orderProcessor->expects($this->once())
            ->method('process')
            ->with($order);

        $this->orderRepository->expects($this->once())
            ->method('add')
            ->with($order);

        $this->reorderService->reorder($orderItem);
    }

    public function testReorderSuccessWithExistingItem(): void
    {
        $orderItem = $this->createMock(OrderItemInterface::class);
        $existingItem = $this->createMock(OrderItemInterface::class);
        $variant = $this->createMock(ProductVariantInterface::class);
        $order = $this->createMock(OrderInterface::class);

        $this->eligibilityChecker->expects($this->once())
            ->method('validate')
            ->with($orderItem);

        $orderItem->expects($this->any())
            ->method('getVariant')
            ->willReturn($variant);

        $orderItem->expects($this->any())
            ->method('getQuantity')
            ->willReturn(2);

        $existingItem->expects($this->any())
            ->method('getVariant')
            ->willReturn($variant);

        $existingItem->expects($this->once())
            ->method('getQuantity')
            ->willReturn(3);

        $this->cartContext->expects($this->once())
            ->method('getCart')
            ->willReturn($order);

        $order->expects($this->once())
            ->method('getItems')
            ->willReturn(new ArrayCollection([$existingItem]));

        $this->quantityModifier->expects($this->once())
            ->method('modify')
            ->with($existingItem, 5);

        $this->orderProcessor->expects($this->once())
            ->method('process')
            ->with($order);

        $this->orderRepository->expects($this->once())
            ->method('add')
            ->with($order);

        $this->reorderService->reorder($orderItem);
    }
}
