<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * You can find more information about us on https://bitbag.io and write us
 * an email on hello@bitbag.io.
 */

declare(strict_types=1);

namespace spec\BitBag\OpenMarketplace\Processor\Order;

use BitBag\OpenMarketplace\Entity\OrderInterface;
use BitBag\OpenMarketplace\Entity\OrderItemInterface;
use BitBag\OpenMarketplace\Entity\VendorInterface;
use BitBag\OpenMarketplace\Manager\OrderManagerInterface;
use BitBag\OpenMarketplace\Processor\Order\SplitOrderByVendorProcessor;
use BitBag\OpenMarketplace\Refresher\PaymentRefresherInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\PaymentInterface;

final class SplitOrderByVendorProcessorSpec extends ObjectBehavior
{
    public function let(
        EntityManager $entityManager,
        OrderManagerInterface $orderManager,
        PaymentRefresherInterface $paymentRefresher
    ): void {
        $this->beConstructedWith(
            $entityManager,
            $orderManager,
            $paymentRefresher
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SplitOrderByVendorProcessor::class);
    }

    public function it_always_creates_at_least_one_secondary_order(
        OrderInterface $order,
        PaymentInterface $payment,
        OrderItemInterface $orderItem,
        OrderInterface $subOrder,
        VendorInterface $vendor,
        OrderManagerInterface $orderManager,
        PaymentRefresherInterface $paymentRefresher
    ): void {
        $orderItemCollection = new ArrayCollection([$orderItem->getWrappedObject()]);
        $paymentCollection = new ArrayCollection([$payment->getWrappedObject()]);
        $order->getItems()->willReturn($orderItemCollection);
        $orderItem->getProductOwner()->willReturn($vendor);
        $order->getPayments()->willReturn($paymentCollection);
        $order->getTotal()->willReturn(100);
        $subOrder->getVendor()->willReturn(null);
        $order->getVendor()->willReturn(null);
        $orderManager->generateNewSecondaryOrder($order, $vendor, $orderItem)->willReturn($subOrder);

        $this->process($order);

        $this->getSecondaryOrdersCount()->shouldReturn(1);

        $paymentRefresher->refreshPayment($subOrder)->shouldHaveBeenCalled();
    }

    public function it_creates_2_secondary_orders_for_products_from_different_vendors(
        OrderInterface $order,
        PaymentInterface $payment,
        OrderItemInterface $orderItem,
        OrderItemInterface $secondItem,
        OrderInterface $subOrder,
        OrderInterface $subOrder2,
        VendorInterface $vendor,
        VendorInterface $vendor2,
        OrderManagerInterface $orderManager,
        PaymentRefresherInterface $paymentRefresher
    ): void {
        $orderItemCollection = new ArrayCollection([$orderItem->getWrappedObject(), $secondItem->getWrappedObject()]);
        $paymentCollection = new ArrayCollection([$payment->getWrappedObject()]);
        $order->getItems()->willReturn($orderItemCollection);

        $orderItem->getProductOwner()->willReturn($vendor);
        $secondItem->getProductOwner()->willReturn($vendor2);
        $order->getPayments()->willReturn($paymentCollection);
        $order->getTotal()->willReturn(100);

        $order->getVendor()->willReturn(null);
        $subOrder->getVendor()->willReturn($vendor);
        $subOrder2->getVendor()->willReturn($vendor2);

        $orderManager->generateNewSecondaryOrder($order, $vendor, $orderItem)->willReturn($subOrder);
        $orderManager->generateNewSecondaryOrder($order, $vendor2, $secondItem)->willReturn($subOrder2);

        $this->process($order);

        $this->getSecondaryOrdersCount()->shouldReturn(2);

        $paymentRefresher->refreshPayment($subOrder)->shouldHaveBeenCalled();
    }
}
