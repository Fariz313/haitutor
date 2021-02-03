<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    const CHANNEL_NOTIF_NAMES = array(
        0 => "CHANNEL_CHAT",
        1 => "CHANNEL_VIDEO_CALL",
        2 => "CHANNEL_ROOM",
        3 => "CHANNEL_VERIFICATION",
        4 => "CHANNEL_PAYMENT_SUCCESS",
        5 => "CHANNEL_REQUEST_JOIN_VIDEO_CALL_ROOM",
        6 => "CHANNEL_CANCEL_REQUEST_JOIN_VIDEO_CALL_ROOM",
        7 => "CHANNEL_REJECT_REQUEST_JOIN_VIDEO_CALL_ROOM",
        8 => "CHANNEL_ACCOUNT_VERIFICATION",
        9 => "CHANNEL_DISBURSEMENT_REQUEST",
        10 => "CHANNEL_DOCUMENT_VERIFICATION",
        11 => "CHANNEL_ROOM_CHAT_CLOSE",
        12 => "CHANNEL_ROOM_CHAT_OPEN",
        13 => "CHANNEL_EBOOK_PURCHASE",
        14 => "CHANNEL_USER_IS_IN_ANOTHER_CALL",
        15 => "CHANNEL_EBOOK_REDEEM",
        16 => "CHANNEL_EBOOK_MANUAL_ORDER"
    );

    const NOTIF_STATUS = array(
        'READ'      => 1,
        'UNREAD'    => 0,
    );

    const NOTIF_ACTION = array(
        'DISBURSEMENT'          => '/disbursement',
        'TUTOR_VERIFICATION'    => '/tutor',
        'EBOOK_REDEEM'          => '/ebookRedeem',
        'EBOOK_MANUAL_ORDER'    => '/ebookOrder',
    );

    const NOTIF_IMAGE = array(
        'DISBURSEMENT'          => '/disbursement',
        'TUTOR_VERIFICATION'    => '/tutor',
        'EBOOK_REDEEM'          => '/ebookRedeem',
        'EBOOK_MANUAL_ORDER'    => '/ebookOrder',
    );

    public function user()
    {
        return $this->hasOne('App\User','id','sender_id');
    }
}
