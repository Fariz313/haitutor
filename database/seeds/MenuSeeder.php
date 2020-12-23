<?php

use App\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {   
        
        DB::table('menu')->insert(
            [
                'name'          => "Dashboard",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-meter",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );

        // Menu User
        DB::table('menu')->insert(
            [
                'name'          => "User",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "fa fa-user-o",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $menuId = DB::getPdo()->lastInsertId();

        // Sub Menu User
        DB::table('menu')->insert(
            [
                'name'          => "Tutor",
                'action_url'    => "/tutor",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "fa fa-graduation-cap",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Student",
                'action_url'    => "/student",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-user-group",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Admin",
                'action_url'    => "/admin",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "fa fa-user-o",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);


        // Menu Room
        DB::table('menu')->insert(
            [
                'name'          => "Room",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-home",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $menuId = DB::getPdo()->lastInsertId();

        // Sub Menu Room
        DB::table('menu')->insert(
            [
                'name'          => "Chat",
                'action_url'    => "/room",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-message-text-outline",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Video Call",
                'action_url'    => "/room_vidcall",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-message-video",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);
        

        // Menu Ebook
        DB::table('menu')->insert(
            [
                'name'          => "Ebook",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $menuId = DB::getPdo()->lastInsertId();

        // Sub Menu Ebook
        DB::table('menu')->insert(
            [
                'name'          => "List Ebook",
                'action_url'    => "/ebook",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Category",
                'action_url'    => "/ebookCategory",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-google-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Library",
                'action_url'    => "/ebookLibrary",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-google-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Redeeem",
                'action_url'    => "/ebookRedeem",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-google-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Manual Order",
                'action_url'    => "/ebookOrder",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-ungroup",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);


        // Menu History Token
        DB::table('menu')->insert(
            [
                'name'          => "History",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-history",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $menuId = DB::getPdo()->lastInsertId();

        // Sub Menu History Token
        DB::table('menu')->insert(
            [
                'name'          => "Payment",
                'action_url'    => "/history",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-note-outline",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Token",
                'action_url'    => "/history_token",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-clock-alert",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);


        // Menu Report
        DB::table('menu')->insert(
            [
                'name'          => "Report",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-warning",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $menuId = DB::getPdo()->lastInsertId();

        // Sub Menu Report
        DB::table('menu')->insert(
            [
                'name'          => "Report Issue",
                'action_url'    => "/history",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-warning",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Report User",
                'action_url'    => "/history_token",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "fa fa-user-o",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);


        // Menu Payment
        DB::table('menu')->insert(
            [
                'name'          => "Payment",
                'action_url'    => "/",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $menuId = DB::getPdo()->lastInsertId();

        // Sub Menu Payment
        DB::table('menu')->insert(
            [
                'name'          => "Payment Method",
                'action_url'    => "/paymentMethod",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "dripicons-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Payment Provider",
                'action_url'    => "/paymentProvider",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-google-wallet",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                'name'          => "Payment Category",
                'action_url'    => "/paymentCategory",
                'action_method' => Menu::ACTION_METHOD["GET"],
                'icon'          => "mdi mdi-ungroup",
                'order'         => 1,
                'is_menu'       => Menu::IS_MENU["MENU"]
            ]
        );
        $subMenuId = DB::getPdo()->lastInsertId();
        $this->addSubMenu($menuId, $subMenuId);

        DB::table('menu')->insert(
            [
                [
                    'name'          => "Disbursement",
                    'action_url'    => "/disbursement",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "dripicons-retweet",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Rating User",
                    'action_url'    => "/rating",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "mdi mdi-account-star-variant",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Package",
                    'action_url'    => "/package",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "mdi mdi-package",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Subject",
                    'action_url'    => "/subject",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "ion-ios7-paper-outline",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Artikel",
                    'action_url'    => "/artikel",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "mdi mdi-paperclip",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "FAQ",
                    'action_url'    => "/faq",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "dripicons-question",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Version",
                    'action_url'    => "/version",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "dripicons-skip",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Information",
                    'action_url'    => "/information",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "dripicons-information",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "About",
                    'action_url'    => "/about",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "dripicons-star",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                ],
                [
                    'name'          => "Setting",
                    'action_url'    => "/setting",
                    'action_method' => Menu::ACTION_METHOD["GET"],
                    'icon'          => "mdi mdi-settings",
                    'order'         => 1,
                    'is_menu'       => Menu::IS_MENU["MENU"]
                    ]
            ]
        );

    }

    private function addSubMenu($idParent, $idChild){
        DB::table('sub_menu')->insert(
            [
                'id_parent_menu'    => $idParent,
                'id_child_menu'     => $idChild
            ]
        );
    }
}
