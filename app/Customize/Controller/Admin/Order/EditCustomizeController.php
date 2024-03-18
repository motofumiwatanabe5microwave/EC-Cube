<?php
namespace Customize\Controller\Admin\Order;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\CustomerRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EditCustomizeController extends AbstractController
{

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * EditController constructor.
     *
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        CustomerRepository $customerRepository,
    ) {
        $this->customerRepository = $customerRepository;
    }

    /**
     * 顧客情報を検索する.
     *
     * @Route("/%eccube_admin_route%/order/search/customer/html", name="admin_order_search_customer_html", methods={"GET", "POST"})
     * @Route("/%eccube_admin_route%/order/search/customer/html/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_order_search_customer_html_page", methods={"GET", "POST"})
     * @Template("@admin/Order/search_customer.twig")
     *
     * @param Request $request
     * @param integer $page_no
     *
     * @return array
     */
    public function searchCustomerHtml(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search customer start.');
            $page_count = $this->eccubeConfig['eccube_default_page_count'];
            $session = $this->session;

            if ('POST' === $request->getMethod()) {
                $page_no = 1;

                $searchData = [
                    'multi' => $request->get('search_word'),
                    'customer_status' => [
                        CustomerStatus::REGULAR,
                    ],
                ];

                $session->set('eccube.admin.order.customer.search', $searchData);
                $session->set('eccube.admin.order.customer.search.page_no', $page_no);
            } else {
                $searchData = (array) $session->get('eccube.admin.order.customer.search');
                if (is_null($page_no)) {
                    $page_no = intval($session->get('eccube.admin.order.customer.search.page_no'));
                } else {
                    $session->set('eccube.admin.order.customer.search.page_no', $page_no);
                }
            }

            $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);

            $event = new EventArgs(
                [
                    'qb' => $qb,
                    'data' => $searchData,
                ],
                $request
            );
            $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_SEARCH);

            /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
            $pagination = $paginator->paginate(
                $qb,
                $page_no,
                $page_count,
                ['wrap-queries' => true]
            );

            /** @var $Customers \Eccube\Entity\Customer[] */
            $Customers = $pagination->getItems();

            if (empty($Customers)) {
                log_debug('search customer not found.');
            }

            $data = [];
            $formatName = '%s%s(%s%s)';
            foreach ($Customers as $Customer) {
                $data[] = [
                    'id' => $Customer->getId(),
                    'name' => sprintf($formatName, $Customer->getName01(), $Customer->getName02(),
                        $Customer->getKana01(),
                        $Customer->getKana02()),
                    // feature-002 電話番号設定変更
                    'phone_number01' => $Customer->getPhoneNumber01(),
                    'phone_number02' => $Customer->getPhoneNumber02(),
                    'phone_number03' => $Customer->getPhoneNumber03(),
                    'email' => $Customer->getEmail(),
                ];
            }

            $event = new EventArgs(
                [
                    'data' => $data,
                    'Customers' => $pagination,
                ],
                $request
            );
            $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_COMPLETE);
            $data = $event->getArgument('data');

            return [
                'data' => $data,
                'pagination' => $pagination,
            ];
        }

        throw new BadRequestHttpException();
    }

    /**
     * 顧客情報を検索する.
     *
     * @Route("/%eccube_admin_route%/order/search/customer/id", name="admin_order_search_customer_by_id", methods={"POST"})
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function searchCustomerById(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search customer by id start.');

            /** @var $Customer \Eccube\Entity\Customer */
            $Customer = $this->customerRepository
                ->find($request->get('id'));

            $event = new EventArgs(
                [
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_BY_ID_INITIALIZE);

            if (is_null($Customer)) {
                log_debug('search customer by id not found.');

                return $this->json([], 404);
            }

            log_debug('search customer by id found.');

            $data = [
                'id' => $Customer->getId(),
                'name01' => $Customer->getName01(),
                'name02' => $Customer->getName02(),
                'kana01' => $Customer->getKana01(),
                'kana02' => $Customer->getKana02(),
                'postal_code' => $Customer->getPostalCode(),
                'pref' => is_null($Customer->getPref()) ? null : $Customer->getPref()->getId(),
                'addr01' => $Customer->getAddr01(),
                'addr02' => $Customer->getAddr02(),
                'email' => $Customer->getEmail(),
                // feature-002 電話番号設定変更
                'phone_number01' => $Customer->getPhoneNumber01(),
                'phone_number02' => $Customer->getPhoneNumber02(),
                'phone_number03' => $Customer->getPhoneNumber03(),
                'company_name' => $Customer->getCompanyName(),
            ];

            $event = new EventArgs(
                [
                    'data' => $data,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_ORDER_EDIT_SEARCH_CUSTOMER_BY_ID_COMPLETE);
            $data = $event->getArgument('data');

            return $this->json($data);
        }

        throw new BadRequestHttpException();
    }

}
