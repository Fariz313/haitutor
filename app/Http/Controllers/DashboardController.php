<?php

namespace App\Http\Controllers;

use App\Disbursement;
use App\Ebook;
use App\EbookLibrary;
use App\EbookOrder;
use App\EbookPurchase;
use App\EbookRedeem;
use App\Helpers\LogApps;
use App\Logs;
use App\Order;
use App\Rating;
use App\Report;
use App\Role;
use App\RoomChat;
use App\RoomVC;
use App\TutorDetail;
use App\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getGeneralStatistics(){
        try {
            $active_room_chat = RoomChat::where('status', 'open')->get();

            $user_active    = RoomChat::select('user_id')->where('status', 'open');
            $tutor_active   = RoomChat::select('tutor_id')->where('status', 'open');
            $active_user_in_room_chat = $user_active->union($tutor_active)->get();

            $active_room_vidcall = RoomVC::where('status', 'open')->get();

            $user_active    = RoomVC::select('user_id')->where('status', 'open');
            $tutor_active   = RoomVC::select('tutor_id')->where('status', 'open');
            $active_user_in_room_vidcall = $user_active->union($tutor_active)->get();

            $tempDate = \Carbon\Carbon::today();
            $transaction_today = Order::where('type_code', 1)
                                ->where('status', 'completed')
                                ->where('created_at', '>=', $tempDate)
                                ->get();

            $active_user_in_transaction_today = Order::select('user_id')
                                ->where('type_code', 1)
                                ->where('status', 'completed')
                                ->where('created_at', '>=', $tempDate)->distinct()->get();

            $report_today = Report::where('created_at', '>=', $tempDate)
                                ->get();

            $active_report_today = Report::where('created_at', '>=', $tempDate)
                                ->distinct()->get();

            $tutor              = User::where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                    ->where('role', Role::ROLE["TUTOR"])->get();

            $student            = User::where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                    ->where('role', Role::ROLE["STUDENT"])->get();

            $published_paid_ebook   = Ebook::where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                                    ->where('type', Ebook::EBOOK_TYPE["PAID"])
                                    ->where('is_published', Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])->get();

            $published_free_ebook   = Ebook::where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                                    ->where('type', Ebook::EBOOK_TYPE["FREE"])
                                    ->where('is_published', Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])->get();
            return [
                'count_active_room_chat'                => count($active_room_chat),
                'count_active_user_in_room_chat'        => count($active_user_in_room_chat),
                'count_active_room_vidcall'             => count($active_room_vidcall),
                'count_active_user_in_room_vidcall'     => count($active_user_in_room_vidcall),
                'count_transaction_today'               => count($active_user_in_transaction_today),
                'count_active_user_in_transaction_today'=> count($transaction_today),
                'count_report_today'                    => count($report_today),
                'count_active_user_in_report_today'     => count($active_report_today),
                'count_student'                         => count($student),
                'count_tutor'                           => count($tutor),
                'count_published_free_ebook'            => count($published_free_ebook),
                'count_published_paid_ebook'            => count($published_paid_ebook)
            ];

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get General Statistics Succeeded',
            //     'data'      => [
            //         'count_active_room_chat'                => count($active_room_chat),
            //         'count_active_user_in_room_chat'        => count($active_user_in_room_chat),
            //         'count_active_room_vidcall'             => count($active_room_vidcall),
            //         'count_active_user_in_room_vidcall'     => count($active_user_in_room_vidcall),
            //         'count_transaction_today'               => count($active_user_in_transaction_today),
            //         'count_active_user_in_transaction_today'=> count($transaction_today),
            //         'count_report_today'                    => count($report_today),
            //         'count_active_user_in_report_today'     => count($active_report_today),
            //         'count_student'                         => count($student),
            //         'count_tutor'                           => count($tutor),
            //         'count_published_free_ebook'            => count($published_free_ebook),
            //         'count_published_paid_ebook'            => count($published_paid_ebook)
            //     ]], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get General Statistics Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getNewStudent(){
        try {
            $NUMBER_USER    = 5;

            $newUser        = User::where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                    ->where('role', Role::ROLE["STUDENT"])
                                    ->orderBy('created_at','DESC')
                                    ->take($NUMBER_USER)->get();
            return $newUser;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get New User Data Succeeded',
            //     'data'      => $newUser
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get New User Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getNewTutor(){
        try {
            $NUMBER_USER    = 5;

            $newUser       = User::where('is_deleted', User::DELETED_STATUS["ACTIVE"])
                                    ->where('role', Role::ROLE["TUTOR"])
                                    ->orderBy('created_at','DESC')
                                    ->take($NUMBER_USER)->get();

            return $newUser;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get New User Data Succeeded',
            //     'data'      => $newUser
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get New User Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getMostReportedUser(){
        try {
            $NUMBER_USER    = 5;

            $userId         = Report::groupBy('target_id')
                                    ->selectRaw('target_id as id, users.name, users.role, count(report.id) as report_count')
                                    ->join("users", "report.target_id", "=", "users.id")
                                    ->orderBy('report_count','DESC')
                                    ->take($NUMBER_USER)->get();
            return $userId;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Most Reported User Data Succeeded',
            //     'data'      => $userId
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Most Reported User Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getBestSellerEbook(){
        try {
            $NUMBER_EBOOK   = 5;

            $data           = EbookLibrary::groupBy('id_ebook')
                                    ->selectRaw('ebook_library.id_ebook as id, ebook.name, count(ebook_library.id_ebook) as ebook_count')
                                    ->join("ebook", "ebook_library.id_ebook", "=", "ebook.id")
                                    ->where("ebook.type", Ebook::EBOOK_TYPE["PAID"])
                                    ->where("ebook.is_deleted", Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                                    ->where("ebook.is_published", Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])
                                    ->orderBy('ebook_count','DESC')
                                    ->take($NUMBER_EBOOK)->get();

            return $data;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Best Seller Ebook Data Succeeded',
            //     'data'      => $data
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Best Seller Ebook Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getPendingTutor(){
        try {
            $NUMBER_USER    = 5;

            $data           = User::join("tutor_detail", "users.id", "=", "tutor_detail.user_id")
                                    ->where('tutor_detail.status', TutorDetail::TutorStatus["PENDING"])
                                    ->where('users.is_deleted', User::DELETED_STATUS["ACTIVE"])
                                    ->select('users.*')
                                    ->with(['detail'])
                                    ->orderBy('tutor_detail.updated_at','DESC')
                                    ->take($NUMBER_USER)->get();
            return $data;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Recent Pending Tutor Data Succeeded',
            //     'data'      => $data
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Recent Pending Tutor Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getPendingEbookRedeem(){
        try {
            $NUMBER_EBOOK   = 5;

            $data           = EbookRedeem::select("ebook_redeem.*", "users.name as customer_name")
                                    ->join("users", "ebook_redeem.id_customer", "=", "users.id")
                                    ->where("ebook_redeem.is_deleted", EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                                    ->where("ebook_redeem.status", EbookRedeem::EBOOK_REDEEM_STATUS["PENDING"])
                                    ->orderBy('ebook_redeem.created_at','DESC')
                                    ->take($NUMBER_EBOOK)->get();

            return $data;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Pending Ebook Redeem Request Data Succeeded',
            //     'data'      => $data
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Pending Ebook Redeem Request Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getPendingEbookManualOrder(){
        try {
            $NUMBER_EBOOK   = 5;

            $data           = EbookOrder::select("ebook_order.*", "users.name as customer_name")
                                    ->join("users", "ebook_order.id_customer", "=", "users.id")
                                    ->where("ebook_order.is_deleted", EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                                    ->where("ebook_order.status", EbookOrder::EBOOK_ORDER_STATUS["PENDING"])
                                    ->orderBy('ebook_order.created_at','DESC')
                                    ->take($NUMBER_EBOOK)->get();

            return $data;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Pending Ebook Manual Order Request Data Succeeded',
            //     'data'      => $data
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Pending Ebook Manual Order Request Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getRatingData(){
        try {
            $MIN_THRESHOLD = 0.05;

            // GET RATING CHAT DATA
            $rating_chat = Rating::selectRaw('rate, count(rate) as count')
                            ->where('serviceable_type', Rating::SERVICEABLE_TYPE["CHAT"])
                            ->groupBy('rate')
                            ->orderBy('count','ASC');

            $label = $rating_chat->pluck('rate')->toArray();
            $count = $rating_chat->pluck('count')->toArray();

            $proper_list_chat = $this->getProperList($label, $count, $MIN_THRESHOLD);

            // GET RATING VIDCALL DATA
            $rating_chat = Rating::selectRaw('rate, count(rate) as count')
                            ->where('serviceable_type', Rating::SERVICEABLE_TYPE["VIDEOCALL"])
                            ->groupBy('rate')
                            ->orderBy('count','ASC');

            $label = $rating_chat->pluck('rate')->toArray();
            $count = $rating_chat->pluck('count')->toArray();

            $proper_list_vidcall = $this->getProperList($label, $count, $MIN_THRESHOLD);

            // GET RATING EBOOK DATA
            $rating_chat = Rating::selectRaw('rate, count(rate) as count')
                            ->where('serviceable_type', Rating::SERVICEABLE_TYPE["EBOOK"])
                            ->groupBy('rate')
                            ->orderBy('count','ASC');

            $label = $rating_chat->pluck('rate')->toArray();
            $count = $rating_chat->pluck('count')->toArray();

            $proper_list_ebook = $this->getProperList($label, $count, $MIN_THRESHOLD);

            return response()->json([
                'status'    => 'Success',
                'message'   => 'Get Rating Statistics Succeeded',
                'data'      => [
                    'rating_chat'       => array(
                        'label' => $proper_list_chat[0],
                        'data'  => array_map('intval', $proper_list_chat[1])
                    ),
                    'rating_vidcall'    => array(
                        'label' => $proper_list_vidcall[0],
                        'data'  => array_map('intval', $proper_list_vidcall[1])
                    ),
                    'rating_ebook'      => array(
                        'label' => $proper_list_ebook[0],
                        'data'  => array_map('intval', $proper_list_ebook[1])
                    ),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Rating Statistics Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    private function getProperList($label, $data, $threshold){
        $stringLabel = array_map('strval', $label);

        $proper_label   = array();
        $proper_data    = array();

        $tempSum = 0;
        $tempJoinLabel = array();
        foreach($data as $idx => $iterCount){
            $tempSum += $iterCount;
            if(($tempSum / array_sum($data)) > $threshold){
                // If Proportion is proper
                if(empty($tempJoinLabel)){
                    // Not Cumulative Component
                    array_push($proper_label, $stringLabel[$idx]);
                    array_push($proper_data, $iterCount);
                } else {
                    // Cumulative Component
                    array_push($tempJoinLabel, $label[$idx]);

                    $tempString = "Lainnya " . json_encode($tempJoinLabel);

                    array_push($proper_label, $tempString);
                    array_push($proper_data, (int)($tempSum));

                    $tempJoinLabel = array();
                }

                $tempSum = 0;
            } else {
                // If Proportion is not proper (less than minimum proportion)
                array_push($tempJoinLabel, $label[$idx]);
            }
        }

        return array($proper_label, $proper_data);
    }

    public function getGraphicOrderData(){
        try {
            $MONTH_DISPLAY  = 12;
            $today = date('Y-m-d');

            // GRAPHIC

            $listTimestamp = array();
            $dataEbook = array();
            $dataToken = array();
            $label = array();

            foreach (range(0, $MONTH_DISPLAY - 1) as $iterMonth) {
                $tempTimestamp = strtotime($today . '-' . $iterMonth . ' months');
                array_push($listTimestamp, $tempTimestamp);
                array_push($dataEbook, 0);
                array_push($dataToken, 0);
            }

            // Ebook Redeem
            foreach ($listTimestamp as $idx => $timestamp) {
                // Ebook Redeem
                $redeemTransactionPerMonth      = EbookRedeem::where('status', EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"])
                                                ->where('is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                                                ->whereYear('created_at', '=', date('Y', $timestamp))
                                                ->whereMonth('created_at', '=', date('m', $timestamp))->pluck('net_price')->toArray();

                $dataEbook[$idx] += array_sum($redeemTransactionPerMonth);

                // Ebook Manual Order
                $orderTransactionPerMonth       = EbookOrder::where('status', EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"])
                                                ->where('is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                                                ->whereYear('created_at', '=', date('Y', $timestamp))
                                                ->whereMonth('created_at', '=', date('m', $timestamp))->pluck('net_price')->toArray();

                $dataEbook[$idx] += array_sum($orderTransactionPerMonth);

                // Ebook Purchase
                $purchaseTransactionPerMonth    = EbookPurchase::where('status', EbookPurchase::EBOOK_PURCHASE_STATUS["SUCCESS"])
                                                ->where('is_deleted', EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["ACTIVE"])
                                                ->whereYear('created_at', '=', date('Y', $timestamp))
                                                ->whereMonth('created_at', '=', date('m', $timestamp))->pluck('amount')->toArray();

                $dataEbook[$idx] += array_sum($purchaseTransactionPerMonth);

                // Token Transaction
                $tokenTransactionPerMonth       = Order::where('status', Order::ORDER_STATUS["COMPLETED"])
                                                ->where('is_deleted', Order::ORDER_DELETED_STATUS["ACTIVE"])
                                                ->where('pos', Order::POS_STATUS["DEBET"])
                                                ->whereYear('created_at', '=', date('Y', $timestamp))
                                                ->whereMonth('created_at', '=', date('m', $timestamp))->pluck('amount')->toArray();

                $dataToken[$idx] += array_sum($tokenTransactionPerMonth);

                // Label
                array_push($label, date('M', $timestamp) . " " . date('Y', $timestamp));
            }

            // STATISTICS
            // Ebook Redeem
            $grandTotalTransactionNumber    = 0;
            $grandTotalTransactionAmount    = 0;

            $redeemTransactionCurrentYear   = EbookRedeem::where('status', EbookRedeem::EBOOK_REDEEM_STATUS["ACTIVE"])
                        ->where('is_deleted', EbookRedeem::EBOOK_REDEEM_DELETED_STATUS["ACTIVE"])
                        ->whereYear('created_at', '=', date('Y', $timestamp))->pluck('net_price')->toArray();

            $grandTotalTransactionAmount    += array_sum($redeemTransactionCurrentYear);
            $grandTotalTransactionNumber    += count($redeemTransactionCurrentYear);

            // Ebook Manual Order
            $orderTransactionCurrentYear    = EbookOrder::where('status', EbookOrder::EBOOK_ORDER_STATUS["ACTIVE"])
                        ->where('is_deleted', EbookOrder::EBOOK_ORDER_DELETED_STATUS["ACTIVE"])
                        ->whereYear('created_at', '=', date('Y', $timestamp))->pluck('net_price')->toArray();

            $grandTotalTransactionAmount    += array_sum($orderTransactionCurrentYear);
            $grandTotalTransactionNumber    += count($orderTransactionCurrentYear);

            // Ebook Purchase
            $purchaseTransactionCurrentYear = EbookPurchase::where('status', EbookPurchase::EBOOK_PURCHASE_STATUS["SUCCESS"])
                        ->where('is_deleted', EbookPurchase::EBOOK_PURCHASE_DELETED_STATUS["ACTIVE"])
                        ->whereYear('created_at', '=', date('Y', $timestamp))->pluck('amount')->toArray();

            $grandTotalTransactionAmount    += array_sum($purchaseTransactionCurrentYear);
            $grandTotalTransactionNumber    += count($purchaseTransactionCurrentYear);

            // Token Transaction
            $tokenTransactionCurrentYear    = Order::where('status', Order::ORDER_STATUS["COMPLETED"])
                        ->where('is_deleted', Order::ORDER_DELETED_STATUS["ACTIVE"])
                        ->where('pos', Order::POS_STATUS["DEBET"])
                        ->whereYear('created_at', '=', date('Y', $timestamp))->pluck('amount')->toArray();

            $grandTotalTransactionAmount    += array_sum($tokenTransactionCurrentYear);
            $grandTotalTransactionNumber    += count($tokenTransactionCurrentYear);

            $result_array   = array();
            foreach ($label as $idx => $iterLabel) {
                $tempArray  = array(
                    'label'         => $iterLabel,
                    'data_token'    => $dataToken[$idx],
                    'data_ebook'    => $dataEbook[$idx]
                );
                array_push($result_array, $tempArray);
            }

            return [
                'graphic'                                   => array_reverse($result_array),
                'grand_total_transaction_amount'            => $grandTotalTransactionAmount,
                'grand_total_transaction_number'            => $grandTotalTransactionNumber,
                'total_ebook_redeem_transaction_amount'     => array_sum($redeemTransactionCurrentYear),
                'total_ebook_order_transaction_amount'      => array_sum($orderTransactionCurrentYear),
                'total_ebook_purchase_transaction_amount'   => array_sum($purchaseTransactionCurrentYear),
                'total_token_transaction_amount'            => array_sum($tokenTransactionCurrentYear)
            ];

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Transaction Statistics Succeeded',
            //     'data'      => [
            //         'graphic'                                   => array_reverse($result_array),
            //         'grand_total_transaction_amount'            => $grandTotalTransactionAmount,
            //         'grand_total_transaction_number'            => $grandTotalTransactionNumber,
            //         'total_ebook_redeem_transaction_amount'     => array_sum($redeemTransactionCurrentYear),
            //         'total_ebook_order_transaction_amount'      => array_sum($orderTransactionCurrentYear),
            //         'total_ebook_purchase_transaction_amount'   => array_sum($purchaseTransactionCurrentYear),
            //         'total_token_transaction_amount'            => array_sum($tokenTransactionCurrentYear)
            //     ]
            // ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Transaction Statistics Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getPendingDisbursement(){
        try {
            $NUMBER_EBOOK   = 5;

            $data           = Disbursement::select("disbursement.*", "users.name as username")
                                    ->join("users", "disbursement.user_id", "=", "users.id")
                                    ->where("disbursement.status", Disbursement::DisbursementStatus["PENDING"])
                                    ->orderBy('disbursement.created_at','DESC')
                                    ->take($NUMBER_EBOOK)->get();

            return $data;

            // return response()->json([
            //     'status'    => 'Success',
            //     'message'   => 'Get Pending Disbursement Request Data Succeeded',
            //     'data'      => $data
            // ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Pending Disbursement Request Data Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getGraphicActivityData(Request $request){
        try {
            $MODE   = 'W';
            if(!is_null($request->get('mode'))){
                $MODE   = $request->get('mode');
            }

            $data = array();
            $label = array();

            $today = date('Y-m-d');

            if($MODE == Logs::DISPLAY_LOG["WEEK"]){
                $DAYS_DISPLAY   = 7;
            } else if($MODE == Logs::DISPLAY_LOG["MONTH"]){
                $DAYS_DISPLAY   = 30;
            } else {
                $DAYS_DISPLAY   = 365;
            }

            foreach (range(0, $DAYS_DISPLAY - 1) as $iterDay) {
                $tempTimestamp = strtotime($today . '-' . $iterDay . ' days');

                $loginData  = Logs::where('log_type', LogApps::LOG_TYPE["LOGIN"])
                                        ->whereDay('created_at', '=', date('d', $tempTimestamp))
                                        ->whereYear('created_at', '=', date('Y', $tempTimestamp))
                                        ->whereMonth('created_at', '=', date('m', $tempTimestamp))->pluck('id')->toArray();

                array_unshift($data, count($loginData));

                // Label
                array_unshift($label, date('d/m/y', $tempTimestamp));
            }

            $result_array   = array();
            foreach ($label as $idx => $iterLabel) {
                $tempArray  = array(
                    'label' => $iterLabel,
                    'data'  => $data[$idx]
                );
                array_push($result_array, $tempArray);
            }

            return response()->json([
                'status'    => 'Success',
                'message'   => 'Get Transaction Statistics Succeeded',
                'data'      => $result_array
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Transaction Statistics Failed',
                'error'     => $e->getMessage()], 500);
        }
    }

    public function getRecentInformationData(){
        try {
            return response()->json([
                'status'    => 'Success',
                'message'   => 'Get Recent Information Succeeded',
                'data'      => [
                    'general_statistics'            => $this->getGeneralStatistics(),
                    'new_student'                   => $this->getNewStudent(),
                    'new_tutor'                     => $this->getNewTutor(),
                    'most_reported_user'            => $this->getMostReportedUser(),
                    'best_seller_ebook'             => $this->getBestSellerEbook(),
                    'pending_tutor_verification'    => $this->getPendingTutor(),
                    'pending_ebook_redeem'          => $this->getPendingEbookRedeem(),
                    'pending_ebook_manual_order'    => $this->getPendingEbookManualOrder(),
                    'pending_disbursement'          => $this->getPendingDisbursement(),
                    'transaction_statistics'        => $this->getGraphicOrderData()
                ]], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get Recent Information Failed',
                'error'     => $e->getMessage()], 500);
        }

    }
}
