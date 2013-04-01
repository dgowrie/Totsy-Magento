<?php
class Crown_Club_Model_Observer {

	/**
	 * Moves expired club members back to the non club members group.
	 * @param Mage_Cron_Model_Schedule $schedule
	 * @since 0.1.0
	 * @return void
	 */
	public function removeExpiredClubMembers($schedule) {
        $env = (string) Mage::getConfig()->getNode('environment');
        if ('production' !== $env) {
            return false;
        }
		$helper = Mage::helper('crownclub');
		if (!$helper->moduleSetupComplete()) return;

		$clubModel = Mage::getModel('crownclub/club');

		$expiredMembers = $clubModel->getExpiredMembers();

		foreach ($expiredMembers as $expiredMember) {
			$customer = Mage::getModel('customer/customer')->load($expiredMember->getId());
            if($customer->getIsInternalUser()) {
                continue;
            }

			$clubModel->removeClubMember($customer)->sendClubMembershipCancelledEmail($customer);
		}
	}

    /**
     * Update points balance after order becomes completed
     *
     * @param Varien_Event_Observer $observer
     * @since 0.4.0
     * @return Crown_Club_Model_Observer
     */
    public function applyCredits($observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();

        // Check if order is club membership
        if ($order->getCustomerIsGuest()
            || !Mage::helper('enterprise_reward')->isEnabledOnFront($order->getStore()->getWebsiteId())
            || !$order->getData('customer_is_club_member')
        ){
            return $this;
        }

        // Check for club item. We don't give credit for allowing credit.
        if ($order->isNominal()) {
            foreach ($order->getAllVisibleItems() as $item) {
                $productModel = Mage::getModel('catalog/product')->load($item->getProductId());
                if ($productModel->getIsClubSubscription()) {
                    return $this;
                }
            }
        }

        // Give credit where credit is due, if it's due that is...
        if ($order->getCustomerId() && Mage_Sales_Model_Order::STATE_COMPLETE == $order->getState()) {
            /* @var $reward Enterprise_Reward_Model_Reward */
            $reward = Mage::getModel('enterprise_reward/reward')
                ->setActionEntity($order)
                ->setCustomerId($order->getCustomerId())
                ->setWebsiteId($order->getStore()->getWebsiteId())
                ->setAction(Crown_Club_Model_Rewards_Reward::REWARD_ACTION_CLUB)
                ->updateRewardPoints();
            if ($reward->getRewardPointsUpdated() && $reward->getPointsDelta()) {
                $order->addStatusHistoryComment(
                    Mage::helper('enterprise_reward')->__('Club customer earned %s for the order.', Mage::helper('enterprise_reward')->formatReward($reward->getPointsDelta()))
                )->save();
            }
        }
        return $this;
    }

    /**
     * Redirect to plus if they access the dashboard page and aren't a member
     *
     * @since 0.7.0
     * @return void
     */
    public function checkPlusMemberDashboardUrl() {
        $customer = Mage::helper('customer')->getCustomer();

        if(!Mage::helper('crownclub')->isClubMember($customer)) {
            if(
                preg_match( '#plus/dashboard#',Mage::app ()->getRequest ()->getRequestString() ) ||
                preg_match( '#plus/credit.html#', Mage::app ()->getRequest ()->getRequestString() ) ||
                preg_match( '#plus/early-access.html#', Mage::app ()->getRequest ()->getRequestString() ) ||
                preg_match( '#plus/exclusive-sales.html#', Mage::app ()->getRequest ()->getRequestString() ) ||
                preg_match( '#plus/entertainment-savings.html#', Mage::app ()->getRequest ()->getRequestString() ) ||
                preg_match( '#plus/discount-vault.html#', Mage::app ()->getRequest ()->getRequestString() )
                ) {
                Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('plus'));
            }
        }

        return;
    }

    /**
     * Check for a default billing address when saving.  If it doesnt exist, ADD IT!
     *
     * @since 0.7.0
     * @return void
     */
    public function checkForDefaultShipping(Varien_Event_Observer $observer) {
        $address = $observer->getEvent()->getQuoteAddress();

        $customer = Mage::helper('customer')->getCustomer();


        if(!$customer->getDefaultBillingAddress()) {
            $address->setIsDefaultBilling(true);
            $address->setSaveInAddressBook(true);
        }
    }
}