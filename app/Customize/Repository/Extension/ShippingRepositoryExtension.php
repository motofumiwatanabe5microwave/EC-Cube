<?php
namespace Customize\Repository\Extension;

use Doctrine\ORM\QueryBuilder;
use Eccube\Repository\ShippingRepository;
use Eccube\Util\StringUtil;

/**
 * ShippingRepository Customize
 *
 * @category   front, admin
 * @author     m.watanabe
 * @version    1.0.0
 */
class ShippingRepositoryExtension extends ShippingRepository
{
    /**
     * Build query
     *
     * @param  array $searchData
     *
     * @return QueryBuilder
     *
     * @deprecated 使用していないので削除予定
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('s');

        $qb->leftJoin('s.OrderItems', 'si')
            ->leftJoin('si.Order', 'o');
        // order_id_start
        if (isset($searchData['shipping_id_start']) && StringUtil::isNotBlank($searchData['shipping_id_start'])) {
            $qb
                ->andWhere('s.id >= :shipping_id_start')
                ->setParameter('shipping_id_start', $searchData['shipping_id_start']);
        }
        // multi
        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            $multi = preg_match('/^\d{0,10}$/', $searchData['multi']) ? $searchData['multi'] : null;
            $qb
                ->andWhere('s.id = :multi OR s.name01 LIKE :likemulti OR s.name02 LIKE :likemulti OR '.
                            's.kana01 LIKE :likemulti OR s.kana02 LIKE :likemulti OR s.company_name LIKE :likemulti')
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%'.$searchData['multi'].'%');
        }

        // shipping_id_end
        if (isset($searchData['shipping_id_end']) && StringUtil::isNotBlank($searchData['shipping_id_end'])) {
            $qb
                ->andWhere('s.id <= :shipping_id_end')
                ->setParameter('shipping_id_end', $searchData['shipping_id_end']);
        }

        // order_id
        if (isset($searchData['order_id']) && StringUtil::isNotBlank($searchData['order_id'])) {
            $qb
                ->andWhere('o.id = :order_id')
                ->setParameter('order_id', $searchData['order_id']);
        }

        // order_no
        if (isset($searchData['order_no']) && StringUtil::isNotBlank($searchData['order_no'])) {
            $qb
                ->andWhere('o.order_no LIKE :order_no')
                ->setParameter('order_no', "%{$searchData['order_no']}%");
        }

        // order status
        if (isset($searchData['order_status']) && count($searchData['order_status'])) {
            $qb
                ->andWhere($qb->expr()->in('o.OrderStatus', ':order_status'))
                ->setParameter('order_status', $searchData['order_status']);
        }
        // name
        if (isset($searchData['name']) && StringUtil::isNotBlank($searchData['name'])) {
            $qb
                ->andWhere('CONCAT(s.name01, s.name02) LIKE :name')
                ->setParameter('name', '%'.$searchData['name'].'%');
        }

        // kana
        if (isset($searchData['kana']) && StringUtil::isNotBlank($searchData['kana'])) {
            $qb
                ->andWhere('CONCAT(s.kana01, s.kana02) LIKE :kana')
                ->setParameter('kana', '%'.$searchData['kana'].'%');
        }

        // order_name
        if (isset($searchData['order_name']) && StringUtil::isNotBlank($searchData['order_name'])) {
            $qb
                ->andWhere('CONCAT(o.name01, o.name02) LIKE :order_name')
                ->setParameter('order_name', '%'.$searchData['order_name'].'%');
        }

        // order_kana
        if (isset($searchData['order_kana']) && StringUtil::isNotBlank($searchData['order_kana'])) {
            $qb
                ->andWhere('CONCAT(o.kana01, s.kana02) LIKE :order_kana')
                ->setParameter('order_kana', '%'.$searchData['order_kana'].'%');
        }

        // order_email
        if (isset($searchData['email']) && StringUtil::isNotBlank($searchData['email'])) {
            $qb
                ->andWhere('o.email like :email')
                ->setParameter('email', '%'.$searchData['email'].'%');
        }

        // feature-002 電話番号設定変更
        // tel
        if (isset($searchData['phone_number']) && StringUtil::isNotBlank($searchData['phone_number'])) {
            $tel = preg_replace('/[^0-9]/', '', $searchData['phone_number']);
            $qb
                ->andWhere('CONCAT(s.phone_number01, s.phone_number02, s.phone_number03) LIKE :phone_number')
                ->setParameter('phone_number', '%'.$tel.'%');
        }

        // payment
        if (!empty($searchData['payment']) && count($searchData['payment'])) {
            $payments = [];
            foreach ($searchData['payment'] as $payment) {
                $payments[] = $payment->getId();
            }
            $qb
                ->leftJoin('o.Payment', 'p')
                ->andWhere($qb->expr()->in('p.id', ':payments'))
                ->setParameter('payments', $payments);
        }

        // oreder_date
        if (!empty($searchData['order_date_start']) && $searchData['order_date_start']) {
            $date = $searchData['order_date_start'];
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        }
        if (!empty($searchData['order_date_end']) && $searchData['order_date_end']) {
            $date = clone $searchData['order_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        }

        // shipping_delivery_date
        if (!empty($searchData['shipping_delivery_date_start']) && $searchData['shipping_delivery_date_start']) {
            $date = $searchData['shipping_delivery_date_start'];
            $qb
                ->andWhere('s.shipping_delivery_date >= :shipping_delivery_date_start')
                ->setParameter('shipping_delivery_date_start', $date);
        }
        if (!empty($searchData['shipping_delivery_date_end']) && $searchData['shipping_delivery_date_end']) {
            $date = clone $searchData['shipping_delivery_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('s.shipping_delivery_date < :shipping_delivery_date_end')
                ->setParameter('shipping_delivery_date_end', $date);
        }

        // shipping_date
        if (!empty($searchData['shipping_date_start']) && $searchData['shipping_date_start']) {
            $date = $searchData['shipping_date_start'];
            $qb
                ->andWhere('s.shipping_date >= :shipping_date_start')
                ->setParameter('shipping_date_start', $date);
        }
        if (!empty($searchData['shipping_date_end']) && $searchData['shipping_date_end']) {
            $date = clone $searchData['shipping_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('s.shipping_date < :shipping_date_end')
                ->setParameter('shipping_date_end', $date);
        }

        // update_date
        if (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $date = $searchData['update_date_start'];
            $qb
                ->andWhere('s.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        }
        if (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('s.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }

        // payment_total
        if (isset($searchData['payment_total_start']) && StringUtil::isNotBlank($searchData['payment_total_start'])) {
            $qb
                ->andWhere('o.payment_total >= :payment_total_start')
                ->setParameter('payment_total_start', $searchData['payment_total_start']);
        }
        if (isset($searchData['payment_total_end']) && StringUtil::isNotBlank($searchData['payment_total_end'])) {
            $qb
                ->andWhere('o.payment_total <= :payment_total_end')
                ->setParameter('payment_total_end', $searchData['payment_total_end']);
        }

        // buy_product_name
        if (isset($searchData['buy_product_name']) && StringUtil::isNotBlank($searchData['buy_product_name'])) {
            $qb
                ->andWhere('si.product_name LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%'.$searchData['buy_product_name'].'%');
        }

        // Order By
        $qb->orderBy('s.update_date', 'DESC');
        $qb->addorderBy('s.id', 'DESC');

        return $qb;
    }
}
