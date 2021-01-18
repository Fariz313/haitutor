<?php

namespace App\Http\Controllers;

use App\Ebook;
use App\Order;
use App\Report;
use App\Role;
use App\RoomChat;
use App\RoomVC;
use App\User;

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

            $published_ebook    = Ebook::where('is_deleted', Ebook::EBOOK_DELETED_STATUS["ACTIVE"])
                                    ->where('is_published', Ebook::EBOOK_PUBLISHED_STATUS["PUBLISHED"])->get();

            return response()->json([
                'status'    => 'Success',
                'message'   => 'Get General Statistics Succeeded',
                'data'      => [
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
                    'count_published_ebook'                 => count($published_ebook)
                ]], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'Failed',
                'message'   => 'Get General Statistics Failed',
                'error'     => $e->getMessage()], 500);
        }
    }
}
