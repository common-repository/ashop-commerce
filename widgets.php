<?php
/**
 * AShopCategoriesWidget Class
 */
class AShopCategoriesWidget extends WP_Widget {
    /** constructor */
    function AShopCategoriesWidget() {
        parent::WP_Widget(false, $name = 'AShop Categories');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $ashop_db;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$shop = apply_filters('widget_shop', $instance['shop']);
		echo $before_widget;
		if (empty($title)) $title = __('Product Categories');
		echo $before_title . $title . $after_title;
		$ashop_categories = ashop_get_categories($ashop_db,$shop);
		$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
		if (is_array($ashop_categories) && !empty($ashop_categories)) {
			echo "<ul>
			";
			if ($shop == "1") foreach ($ashop_categories as $categoryid=>$categoryname) echo "<li><a href=\"$ashop_url/index.php?cat=$categoryid\">$categoryname</a></li>
			";
			else foreach ($ashop_categories as $categoryid=>$categoryname) echo "<li><a href=\"$ashop_url/index.php?cat=$categoryid&shop=$shop\">$categoryname</a></li>
			";
			echo "</ul>
			";
		}
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['shop'] = strip_tags($new_instance['shop']);
		return $instance;
	}

    /** @see WP_Widget::form */
    function form($instance) {
		global $ashop_db;
        $title = esc_attr($instance['title']);
		$shop = esc_attr($instance['shop']);
		$shops = ashop_get_shops($ashop_db);
		$shoplist = "";
		if (is_array($shops) && count($shops) > 1) {
			$shoplist = "<p>
			<label for=\"".$this->get_field_id('shop')."\">".__('Shop').":
			<select class=\"widefat\" id=\"".$this->get_field_id('shop')."\" name=\"".$this->get_field_name('shop')."\">\n";
			foreach ($shops as $shopid=>$shopname) {
				$shoplist .= "<option value=\"$shopid\"";
				if ($shopid == $shop) $shoplist .= " selected";
				$shoplist .= ">$shopname</option>\n";
			}
			$shoplist .= "</select></p>";
		} else $shoplist = "<input id=\"".$this->get_field_id('shop')."\" name=\"".$this->get_field_name('shop')."\" type=\"hidden\" value=\"1\" />";
		echo "
         <p>
          <label for=\"".$this->get_field_id('title')."\">";
		  _e('Title:');
		  echo "</label> 
          <input class=\"widefat\" id=\"".$this->get_field_id('title')."\" name=\"".$this->get_field_name('title')."\" type=\"text\" value=\"$title\" />
        </p>
		  $shoplist
		";
    }

} // class AShopCategoriesWidget

/**
 * AShopShopsWidget Class
 */
class AShopShopsWidget extends WP_Widget {
    /** constructor */
    function AShopShopsWidget() {
        parent::WP_Widget(false, $name = 'AShop Shops');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $ashop_db;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		echo $before_widget;
		if (empty($title)) $title = __('Shopping Mall');
		echo $before_title . $title . $after_title;
		$ashop_shops = ashop_get_shops($ashop_db);
		$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
		if (is_array($ashop_shops)) {
			echo "<ul>
			";
			foreach ($ashop_shops as $shop=>$shopname) {
				if ($shop == "1") echo "<li><a href=\"$ashop_url\">$shopname</a></li>
				";
				else echo "<li><a href=\"$ashop_url/index.php?shop=$shop\">$shopname</a></li>
				";
			}
			echo "</ul>
			";
		}
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

    /** @see WP_Widget::form */
    function form($instance) {
        $title = esc_attr($instance['title']);
		echo "
         <p>
          <label for=\"".$this->get_field_id('title')."\">";
		  _e('Title:');
		  echo "</label> 
          <input class=\"widefat\" id=\"".$this->get_field_id('title')."\" name=\"".$this->get_field_name('title')."\" type=\"text\" value=\"$title\" />
        </p>";
    }

} // class AShopShopsWidget

/**
 * AShopTopListWidget Class
 */
class AShopTopListWidget extends WP_Widget {
    /** constructor */
    function AShopTopListWidget() {
        parent::WP_Widget(false, $name = 'AShop Top Sellers');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $ashop_db;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$shop = apply_filters('widget_shop', $instance['shop']);
		$items = apply_filters('widget_items', $instance['items']);
		echo $before_widget;
		if (empty($title)) $title = __('Top Sellers');
		echo $before_title . $title . $after_title;
		$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
		$ashoppath = ashop_get_preference('ashoppath', $ashop_db);
		if (file_exists("$ashoppath/includes/toplist.inc.php")) {
			$currentpath = getcwd();
			chdir($ashoppath);
			$redirect = "$ashop_url/index.php";
			$layout = 1;
			$mode = "list";
			$db = $ashop_db;
			$databaseserver = "dummyvalue";
			$databaseuser = "dummyvalue";
			$lang = ashop_get_preference('defaultlanguage', $ashop_db);
			include "$ashoppath/includes/toplist.inc.php";
			chdir($currentpath);
		}
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['shop'] = strip_tags($new_instance['shop']);
		$instance['items'] = strip_tags($new_instance['items']);
		return $instance;
	}

    /** @see WP_Widget::form */
    function form($instance) {
		global $ashop_db;
        $title = esc_attr($instance['title']);
		$shop = esc_attr($instance['shop']);
		$items = esc_attr($instance['items']);
		$shops = ashop_get_shops($ashop_db);
		$shoplist = "";
		if (is_array($shops) && count($shops) > 1) {
			$shoplist = "<p>
			<label for=\"".$this->get_field_id('shop')."\">".__('Shop').":
			<select class=\"widefat\" id=\"".$this->get_field_id('shop')."\" name=\"".$this->get_field_name('shop')."\">\n";
			foreach ($shops as $shopid=>$shopname) {
				$shoplist .= "<option value=\"$shopid\"";
				if ($shopid == $shop) $shoplist .= " selected";
				$shoplist .= ">$shopname</option>\n";
			}
			$shoplist .= "</select></p>";
		} else $shoplist = "<input id=\"".$this->get_field_id('shop')."\" name=\"".$this->get_field_name('shop')."\" type=\"hidden\" value=\"1\" />";
		echo "
         <p>
          <label for=\"".$this->get_field_id('title')."\">";
		  _e('Title:');
		  echo "</label> 
          <input class=\"widefat\" id=\"".$this->get_field_id('title')."\" name=\"".$this->get_field_name('title')."\" type=\"text\" value=\"$title\" />
        </p>
         <p>
          <label for=\"".$this->get_field_id('items')."\">";
		  _e('Number of items:');
		  echo "</label> 
          <input class=\"widefat\" id=\"".$this->get_field_id('items')."\" name=\"".$this->get_field_name('items')."\" type=\"text\" value=\"$items\" />
        </p>		
		$shoplist
		";
    }

} // class AShopTopListWidget

/**
 * AShopLatestAdditionsWidget Class
 */
class AShopLatestAdditionsWidget extends WP_Widget {
    /** constructor */
    function AShopLatestAdditionsWidget() {
        parent::WP_Widget(false, $name = 'AShop Latest Additions');
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
		global $ashop_db;
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
		$shop = apply_filters('widget_shop', $instance['shop']);
		$items = apply_filters('widget_items', $instance['items']);
		echo $before_widget;
		if (empty($title)) $title = __('Latest Additions');
		echo $before_title . $title . $after_title;
		$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
		$ashoppath = ashop_get_preference('ashoppath', $ashop_db);
		if (file_exists("$ashoppath/includes/toplist.inc.php")) {
			$currentpath = getcwd();
			chdir($ashoppath);
			$redirect = "$ashop_url/index.php";
			$layout = 2;
			$mode = "list";
			$db = $ashop_db;
			$databaseserver = "dummyvalue";
			$databaseuser = "dummyvalue";
			$lang = ashop_get_preference('defaultlanguage', $ashop_db);
			include "$ashoppath/includes/toplist.inc.php";
			chdir($currentpath);
		}
		echo $after_widget;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['shop'] = strip_tags($new_instance['shop']);
		$instance['items'] = strip_tags($new_instance['items']);
		return $instance;
	}

    /** @see WP_Widget::form */
    function form($instance) {
		global $ashop_db;
        $title = esc_attr($instance['title']);
		$shop = esc_attr($instance['shop']);
		$items = esc_attr($instance['items']);
		$shops = ashop_get_shops($ashop_db);
		$shoplist = "";
		if (is_array($shops) && count($shops) > 1) {
			$shoplist = "<p>
			<label for=\"".$this->get_field_id('shop')."\">".__('Shop').":
			<select class=\"widefat\" id=\"".$this->get_field_id('shop')."\" name=\"".$this->get_field_name('shop')."\">\n";
			foreach ($shops as $shopid=>$shopname) {
				$shoplist .= "<option value=\"$shopid\"";
				if ($shopid == $shop) $shoplist .= " selected";
				$shoplist .= ">$shopname</option>\n";
			}
			$shoplist .= "</select></p>";
		} else $shoplist = "<input id=\"".$this->get_field_id('shop')."\" name=\"".$this->get_field_name('shop')."\" type=\"hidden\" value=\"1\" />";
		echo "
         <p>
          <label for=\"".$this->get_field_id('title')."\">";
		  _e('Title:');
		  echo "</label> 
          <input class=\"widefat\" id=\"".$this->get_field_id('title')."\" name=\"".$this->get_field_name('title')."\" type=\"text\" value=\"$title\" />
        </p>
         <p>
          <label for=\"".$this->get_field_id('items')."\">";
		  _e('Number of items:');
		  echo "</label> 
          <input class=\"widefat\" id=\"".$this->get_field_id('items')."\" name=\"".$this->get_field_name('items')."\" type=\"text\" value=\"$items\" />
        </p>		
		$shoplist
		";
    }

} // class AShopLatestAdditionsWidget

/**
 * AutoresponderWidget Class
 */
//class AutoresponderWidget extends WP_Widget {
    /** constructor */
//    function AutoresponderWidget() {
//        parent::WP_Widget(false, $name = 'Autoresponder Signup');
//    }

    /** @see WP_Widget::widget */
//    function widget($args, $instance) {
//		global $ashop_db;
//        extract( $args );
//        $title = apply_filters('widget_title', $instance['title']);
//		$armessage = apply_filters('widget_armessage', $instance['armessage']);
//		$arthankyoumessage = apply_filters('widget_arthankyoumessage', $instance['arthankyoumessage']);
//		$aruserid = ashop_get_preference('autoresponderid', $ashop_db);
//		$autoresponderid = apply_filters('widget_autoresponder', $instance['autoresponderid']);
//		$arprofileid = ashop_get_arprofileid($ashop_db, $autoresponderid);
//		if (isset($_POST["ar_subscriber_name"])) $ar_subscriber_name = $_POST["ar_subscriber_name"];
//		else $ar_subscriber_name = "";
//		if (isset($_POST["ar_subscriber_email"])) $uncheckedemail = $_POST["ar_subscriber_email"];
//		else $uncheckedemail = "";
//		// Validate email...
//		if (preg_match("/^[[:alnum:]][a-z0-9_\.\-]*@[a-z0-9\.\-]+\.[a-z]{2,4}$/", $uncheckedemail)) $ar_subscriber_email = $uncheckedemail;
//		else $ar_subscriber_email = "";
//		if (!empty($autoresponderid) && is_numeric($autoresponderid) && !empty($arprofileid) && is_numeric($arprofileid)) {
//			echo $before_widget;
//			if (empty($title)) $title = __('Newsletter Signup');
//			echo $before_title . $title . $after_title;
//			if (empty($ar_subscriber_name) || empty($ar_subscriber_email)) {
//				if (!empty($ar_subscriber_name)) $armessage = __('Please enter a valid email!');
//				if (!isset($form)) $form = "";
//				$form .= '<div id="ARform"><p>';
//				$form .= $armessage;
//				$form .= '</p><form action="" method="post">';
//				$form .= '<div class="ARf-row">';
//				$form .= '<label for="ar_subscriber_name">'. __('Name:') .'</label>';
//				$form .= '<input id="ar_subscriber_name" name="ar_subscriber_name" type="text" value="'.$ar_subscriber_name.'"/>';
//				$form .= '</div>';
//				$form .= '<div class="ARf-row">';
//				$form .= '<label for="ar_subscriber_email">'. __('Email:') .'</label>';
//				$form .= '<input id="ar_subscriber_email" name="ar_subscriber_email" type="text" value="'.$ar_subscriber_email.'"/>';
//				$form .= '</div>';
//				$form .= '<div class="ARf-hCnt">';
//				$form .= '<input type="submit" class="ARfh-In" value="'. __('Subscribe') .'" />';
//				$form .= '</div>';
//				echo $form;
//			} else {
//				echo "<p>".$arthankyoumessage."</p>";
//				$querystring = "v=$aruserid&w=$arprofileid&subscription_type=E&id=$autoresponderid&full_name=$ar_subscriber_name&email=$ar_subscriber_email&posted=true";
//				$postheader = "POST /formcapture.php HTTP/1.0\r\nHost: autoresponder-service.com\r\nContent-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen ($querystring)."\r\n\r\n";
//				$fp = @fsockopen ("autoresponder-service.com", 80, $errno, $errstr, 10);
//				$res = "";
//				if ($fp) {
//					@fputs ($fp, $postheader.$querystring);
//					//while (!feof($fp)) $res .= fgets ($fp, 1024);
//					@fclose ($fp);
//				}
//			}
//			echo $after_widget;
//		}
//    }

    /** @see WP_Widget::update */
//    function update($new_instance, $old_instance) {
//		$instance = $old_instance;
//		$instance['title'] = strip_tags($new_instance['title']);
//		$instance['armessage'] = strip_tags($new_instance['armessage']);
//		$instance['arthankyoumessage'] = strip_tags($new_instance['arthankyoumessage']);
//		$instance['autoresponderid'] = strip_tags($new_instance['autoresponderid']);
//		return $instance;
//	}

    /** @see WP_Widget::form */
//    function form($instance) {
//		global $ashop_db;
//        $title = esc_attr($instance['title']);
//		$armessage = esc_attr($instance['armessage']);
//		$arthankyoumessage = esc_attr($instance['arthankyoumessage']);
//		$autoresponderid = esc_attr($instance['autoresponderid']);
//		$autoresponders = ashop_get_autoresponders($ashop_db);
//		$arlist = "";
//		if (is_array($autoresponders)) {
//			$arlist = "<p>
//			<label for=\"".$this->get_field_id('autoresponderid')."\">".__('Autoresponder').":
//			<select class=\"widefat\" id=\"".$this->get_field_id('autoresponderid')."\" name=\"".$this->get_field_name('autoresponderid')."\">\n";
//			foreach ($autoresponders as $thisautoresponderid=>$autorespondername) {
//				$arlist .= "<option value=\"$thisautoresponderid\"";
//				if ($thisautoresponderid == $autoresponderid) $arlist .= " selected";
//				$arlist .= ">$autorespondername</option>\n";
//			}
//			$arlist .= "</select></p>";
//		}
//		echo "
//         <p>
//          <label for=\"".$this->get_field_id('title')."\">";
//		  _e('Title:');
//		  echo "</label> 
//          <input class=\"widefat\" id=\"".$this->get_field_id('title')."\" name=\"".$this->get_field_name('title')."\" type=\"text\" value=\"$title\" />
//        </p>
//         <p>
//          <label for=\"".$this->get_field_id('items')."\">";
//		  _e('Message:');
//		  echo "</label> 
//          <input class=\"widefat\" id=\"".$this->get_field_id('armessage')."\" name=\"".$this->get_field_name('armessage')."\" type=\"text\" value=\"$armessage\" />
//        </p>
//         <p>
//          <label for=\"".$this->get_field_id('items')."\">";
//		  _e('Thank you message:');
//		  echo "</label> 
//          <input class=\"widefat\" id=\"".$this->get_field_id('arthankyoumessage')."\" name=\"".$this->get_field_name('arthankyoumessage')."\" type=\"text\" value=\"$arthankyoumessage\" />
//        </p>
//		$arlist
//		";
//    }

//} // class AutoresponderWidget
?>