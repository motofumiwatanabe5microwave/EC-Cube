<?php
namespace Customize\Repository\Extension;

use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\Sex;
use Eccube\Entity\Payment;
use Eccube\Entity\Shipping;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\QueryKey;
use Eccube\Util\StringUtil;

/**
 * OrderRepository Customize
 *
 * @category   front, admin
 * @author     m.watanabe
 * @version    1.0.0
 */
class OrderRepositoryExtension extends OrderRepository
{
    /**
     * @param array{
     *         order_id?:string|int,
     *         order_no?:string,
     *         order_id_start?:string|int,
     *         order_id_end?:string|int,
     *         multi?:string|int|null,
     *         status?:OrderStatus[]|int[],
     *         company_name?:string,
     *         name?:string,
     *         kana?:string,
     *         email?:string,
     *         phone_number?:string,
     *         sex?:Sex[],
     *         payment?:Payment[],
     *         order_datetime_start?:\DateTime,
     *         order_datetime_end?:\DateTime,
     *         order_date_start?:\DateTime,
     *         order_date_end?:\DateTime,
     *         payment_datetime_start?:\DateTime,
     *         payment_datetime_end?:\DateTime,
     *         payment_date_start?:\DateTime,
     *         payment_date_end?:\DateTime,
     *         update_datetime_start?:\DateTime,
     *         update_datetime_end?:\DateTime,
     *         update_date_start?:\DateTime,
     *         update_date_end?:\DateTime,
     *         payment_total_start?:string|int,
     *         payment_total_end?:string|int,
     *         payment_product_name?:string,
     *         shipping_mail?:Shipping::SHIPPING_MAIL_UNSENT|Shipping::SHIPPING_MAIL_SENT,
     *         tracking_number?:string,
     *         shipping_delivery_datetime_start?:\DateTime,
     *         shipping_delivery_datetime_end?:\DateTime,
     *         shipping_delivery_date_start?:\DateTime,
     *         shipping_delivery_date_end?:\DateTime,
     *         sortkey?:string,
     *         sorttype?:string
     *     } $searchData
     *
     * @return QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o, s')
            ->addSelect('oi', 'pref')
            ->leftJoin('o.OrderItems', 'oi')
            ->leftJoin('o.Pref', 'pref')
            ->innerJoin('o.Shippings', 's');

        // order_id_start
        if (isset($searchData['order_id']) && StringUtil::isNotBlank($searchData['order_id'])) {
            $qb
                ->andWhere('o.id = :order_id')
                ->setParameter('order_id', $searchData['order_id']);
        }

        // order_no
        if (isset($searchData['order_no']) && StringUtil::isNotBlank($searchData['order_no'])) {
            $qb
                ->andWhere('o.order_no = :order_no')
                ->setParameter('order_no', $searchData['order_no']);
        }

        // order_id_start
        if (isset($searchData['order_id_start']) && StringUtil::isNotBlank($searchData['order_id_start'])) {
            $qb
                ->andWhere('o.id >= :order_id_start')
                ->setParameter('order_id_start', $searchData['order_id_start']);
        }
        // multi
        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            // スペース除去
            $clean_key_multi = preg_replace('/\s+|[　]+/u', '', $searchData['multi']);
            $multi = preg_match('/^\d{0,10}$/', $clean_key_multi) ? $clean_key_multi : null;
            if ($multi && $multi > '2147483647' && $this->isPostgreSQL()) {
                $multi = null;
            }
            // feature-002 電話番号設定変更
            $qb
                ->andWhere('o.id = :multi OR CONCAT(o.name01, o.name02) LIKE :likemulti OR '.
                    "CONCAT(COALESCE(o.kana01, ''), COALESCE(o.kana02, '')) LIKE :likemulti OR o.company_name LIKE :multi_company_name OR ".
                    'o.order_no LIKE :likemulti OR o.email LIKE :likemulti OR CONCAT(o.phone_number01, o.phone_number02, o.phone_number03) LIKE :likemulti')
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%'.$clean_key_multi.'%')
                ->setParameter('multi_company_name', '%'.$searchData['multi'].'%'); // 会社名はスペースを除去せず検索
        }

        // order_id_end
        if (isset($searchData['order_id_end']) && StringUtil::isNotBlank($searchData['order_id_end'])) {
            $qb
                ->andWhere('o.id <= :order_id_end')
                ->setParameter('order_id_end', $searchData['order_id_end']);
        }

        // status
        $filterStatus = false;
        if (!empty($searchData['status']) && count($searchData['status'])) {
            $qb
                ->andWhere($qb->expr()->in('o.OrderStatus', ':status'))
                ->setParameter('status', $searchData['status']);
            $filterStatus = true;
        }

        if (!$filterStatus) {
            // 購入処理中, 決済処理中は検索対象から除外
            $qb->andWhere($qb->expr()->notIn('o.OrderStatus', ':status'))
                ->setParameter('status', [OrderStatus::PROCESSING, OrderStatus::PENDING]);
        }

        // company_name
        if (isset($searchData['company_name']) && StringUtil::isNotBlank($searchData['company_name'])) {
            $qb
                ->andWhere('o.company_name LIKE :company_name')
                ->setParameter('company_name', '%'.$searchData['company_name'].'%');
        }

        // name
        if (isset($searchData['name']) && StringUtil::isNotBlank($searchData['name'])) {
            $clean_name = preg_replace('/\s+|[　]+/u', '', $searchData['name']);
            $qb
                ->andWhere('CONCAT(o.name01, o.name02) LIKE :name')
                ->setParameter('name', '%'.$clean_name.'%');
        }

        // kana
        if (isset($searchData['kana']) && StringUtil::isNotBlank($searchData['kana'])) {
            $clean_kana = preg_replace('/\s+|[　]+/u', '', $searchData['kana']);
            $qb
                ->andWhere("CONCAT(COALESCE(o.kana01, ''), COALESCE(o.kana02, '')) LIKE :kana")
                ->setParameter('kana', '%'.$clean_kana.'%');
        }

        // email
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
                ->andWhere('CONCAT(o.phone_number01, o.phone_number02, o.phone_number03) LIKE :phone_number')
                ->setParameter('phone_number', '%'.$tel.'%');
        }

        // sex
        if (!empty($searchData['sex']) && count($searchData['sex']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('o.Sex', ':sex'))
                ->setParameter('sex', $searchData['sex']->toArray());
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
        if (!empty($searchData['order_datetime_start']) && $searchData['order_datetime_start']) {
            $date = $searchData['order_datetime_start'];
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        } elseif (!empty($searchData['order_date_start']) && $searchData['order_date_start']) {
            $date = $searchData['order_date_start'];
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        }

        if (!empty($searchData['order_datetime_end']) && $searchData['order_datetime_end']) {
            $date = $searchData['order_datetime_end'];
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        } elseif (!empty($searchData['order_date_end']) && $searchData['order_date_end']) {
            $date = clone $searchData['order_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        }

        // payment_date
        if (!empty($searchData['payment_datetime_start']) && $searchData['payment_datetime_start']) {
            $date = $searchData['payment_datetime_start'];
            $qb
                ->andWhere('o.payment_date >= :payment_date_start')
                ->setParameter('payment_date_start', $date);
        } elseif (!empty($searchData['payment_date_start']) && $searchData['payment_date_start']) {
            $date = $searchData['payment_date_start'];
            $qb
                ->andWhere('o.payment_date >= :payment_date_start')
                ->setParameter('payment_date_start', $date);
        }

        if (!empty($searchData['payment_datetime_end']) && $searchData['payment_datetime_end']) {
            $date = $searchData['payment_datetime_end'];
            $qb
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('payment_date_end', $date);
        } elseif (!empty($searchData['payment_date_end']) && $searchData['payment_date_end']) {
            $date = clone $searchData['payment_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.payment_date < :payment_date_end')
                ->setParameter('payment_date_end', $date);
        }

        // update_date
        if (!empty($searchData['update_datetime_start']) && $searchData['update_datetime_start']) {
            $date = $searchData['update_datetime_start'];
            $qb
                ->andWhere('o.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        } elseif (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $date = $searchData['update_date_start'];
            $qb
                ->andWhere('o.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        }

        if (!empty($searchData['update_datetime_end']) && $searchData['update_datetime_end']) {
            $date = $searchData['update_datetime_end'];
            $qb
                ->andWhere('o.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        } elseif (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.update_date < :update_date_end')
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
                ->andWhere('oi.product_name LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%'.$searchData['buy_product_name'].'%');
        }

        // 発送メール送信/未送信.
        if (isset($searchData['shipping_mail']) && $count = count($searchData['shipping_mail'])) {
            // 送信済/未送信両方にチェックされている場合は検索条件に追加しない
            if ($count < 2) {
                $checked = current($searchData['shipping_mail']);
                if ($checked == Shipping::SHIPPING_MAIL_UNSENT) {
                    // 未送信
                    $qb
                        ->andWhere('s.mail_send_date IS NULL');
                } elseif ($checked == Shipping::SHIPPING_MAIL_SENT) {
                    // 送信
                    $qb
                        ->andWhere('s.mail_send_date IS NOT NULL');
                }
            }
        }

        // 送り状番号.
        if (!empty($searchData['tracking_number'])) {
            $qb
                ->andWhere('s.tracking_number = :tracking_number')
                ->setParameter('tracking_number', $searchData['tracking_number']);
        }

        // お届け予定日(Shipping.delivery_date)
        if (!empty($searchData['shipping_delivery_datetime_start']) && $searchData['shipping_delivery_datetime_start']) {
            $date = $searchData['shipping_delivery_datetime_start'];
            $qb
                ->andWhere('s.shipping_delivery_date >= :shipping_delivery_date_start')
                ->setParameter('shipping_delivery_date_start', $date);
        } elseif (!empty($searchData['shipping_delivery_date_start']) && $searchData['shipping_delivery_date_start']) {
            $date = $searchData['shipping_delivery_date_start'];
            $qb
                ->andWhere('s.shipping_delivery_date >= :shipping_delivery_date_start')
                ->setParameter('shipping_delivery_date_start', $date);
        }

        if (!empty($searchData['shipping_delivery_datetime_end']) && $searchData['shipping_delivery_datetime_end']) {
            $date = $searchData['shipping_delivery_datetime_end'];
            $qb
                ->andWhere('s.shipping_delivery_date < :shipping_delivery_date_end')
                ->setParameter('shipping_delivery_date_end', $date);
        } elseif (!empty($searchData['shipping_delivery_date_end']) && $searchData['shipping_delivery_date_end']) {
            $date = clone $searchData['shipping_delivery_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('s.shipping_delivery_date < :shipping_delivery_date_end')
                ->setParameter('shipping_delivery_date_end', $date);
        }

        // Order By
        if (isset($searchData['sortkey']) && !empty($searchData['sortkey'])) {
            $sortOrder = (isset($searchData['sorttype']) && $searchData['sorttype'] == 'a') ? 'ASC' : 'DESC';

            $qb->orderBy(self::COLUMNS[$searchData['sortkey']], $sortOrder);
            $qb->addOrderBy('o.update_date', 'DESC');
            $qb->addOrderBy('o.id', 'DESC');
        } else {
            $qb->orderBy('o.update_date', 'DESC');
            $qb->addorderBy('o.id', 'DESC');
        }

        return $this->queries->customize(QueryKey::ORDER_SEARCH_ADMIN, $qb, $searchData);
    }
}
