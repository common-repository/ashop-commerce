<?php
function ashopname_func( $atts ) {
	global $ashop_db;
	extract( shortcode_atts( array(
		'link' => 'no',
		'target' => '',
	), $atts ) );

	$ashop_name = ashop_get_preference('ashopname', $ashop_db);
	if ($link == 'yes') {
		$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
		$ashop_name = "<a href=\"$ashop_url\" target=\"$target\">$ashop_name</a>";
	}
	return $ashop_name;
}
add_shortcode( 'ashopname', 'ashopname_func' );

function ashopcategory_func( $atts ) {
	global $ashop_db;
	extract( shortcode_atts( array(
		'id' => '',
		'shop' => '',
		'startitem' => '1',
		'items' => '',
	), $atts ) );

	$productlist = "";

	if (!empty($id) && is_numeric($id)) {
		if (empty($shop) || !is_numeric($shop)) $shop = 1;

		$ashop_path = ashop_get_preference('ashoppath', $ashop_db);
		$ashop_url = ashop_get_preference('ashopurl', $ashop_db);
		if (!empty($ashop_path) && file_exists("$ashop_path/admin/ashopconstants.inc.php")) include "$ashop_path/admin/ashopconstants.inc.php";
		$ashopsortorder = ashop_get_preference('ashopsortorder', $ashop_db);
		$membershops = ashop_get_preference('membershops', $ashop_db);
		$thumbnailwidth = ashop_get_preference('thumbnailwidth', $ashop_db);

		if (!$membershops) $shopsearch = "%";
		else $shopsearch = $shop;

		$sql = "SELECT product.* from productcategory, product WHERE productcategory.categoryid = '$id' AND product.productid = productcategory.productid AND active='1' AND ";
		if ($shop == "1") $sql .= "(product.userid LIKE '$shopsearch' OR product.inmainshop='1')";
		else $sql .= "product.userid LIKE '$shopsearch'"; 
		$sql .= " ORDER BY product.ordernumber $ashopsortorder";
		$result = @mysqli_query($ashop_db, $sql);
		if (empty($items)) $items = @mysqli_num_rows($result);
		if ($startitem > 1) @mysqli_data_seek($result, $startitem);
		$thisitem = 1;

		while ($row = @mysqli_fetch_array($result)) {
			if ($thisitem > $items) break;
			$copyof = $row["copyof"];
			if (!empty($copyof)) {
				$copyresult = @mysqli_query($ashop_db, "SELECT * FROM product WHERE productid='$copyof'");
				$row = @mysqli_fetch_array($copyresult);
			}
			$productid = $row["productid"];
			$name = $row["name"];
			if (!empty($row["pricetext"])) $price = $row["pricetext"];
			else {
				$price = $row["price"];
				$ashop_currency = ashop_get_preference('ashopcurrency', $ashop_db);
				$price = $currencysymbols[$ashop_currency]["pre"].$price.$currencysymbols[$ashop_currency]["post"];
			}
			$description = $row["description"];
			$detailsurl = $row["detailsurl"];
			if (empty($detailsurl)) {
				if ($shop == 1) $detailsurl = "$ashop_url/index.php?product=$productid";
				else $detailsurl = "$ashop_url/index.php?product=$productid&shop=$shop";
			}
			$productlist .= "
			<div class=\"ashop_product\">";
			if (file_exists("$ashop_path/prodimg/$productid.jpg")) {
				$imagesize = getimagesize("$ashop_path/prodimg/$productid.jpg");
				if ($imagesize[0] == $thumbnailwidth) {
					$imagesizestring = $imagesize[3];
					$imageheight = $imagesize[1];
				} else {
					$imagesizeratio = $thumbnailwidth/$imagesize[0];
					$imageheight = $imagesize[1]*$imagesizeratio;
					$imagesizestring = "width=\"$thumbnailwidth\" height=\"$imageheight\"";
				}
				$productlist .= "
				<div class=\"ashop_prodimg\" style=\"width: $thumbnailwidth; height: $imageheight;\"><a href=\"$detailsurl\"><img $imagesizestring src=\"$ashop_url/prodimg/$productid.jpg\" alt=\"$name\" border=\"0\"></a></div>";
			} else if (file_exists("$ashop_path/prodimg/$productid.gif")) {
				$imagesize = getimagesize("$ashop_path/prodimg/$productid.gif");
				if ($imagesize[0] == $thumbnailwidth) {
					$imagesizestring = $imagesize[3];
					$imageheight = $imagesize[1];
				}
				else {
					$imagesizeratio = $thumbnailwidth/$imagesize[0];
					$imageheight = $imagesize[1]*$imagesizeratio;
					$imagesizestring = "width=\"$thumbnailwidth\" height=\"$imageheight\"";
				}
				$productlist .= "
				<div class=\"ashop_prodimg\" style=\"width: $thumbnailwidth; height: $imageheight;\"><a href=\"$detailsurl\"><img $imagesizestring src=\"$ashop_url/prodimg/$productid.gif\" alt=\"$name\" border=\"0\"></a></div>";
			}
			$productlist .= "<h2><a href=\"$detailsurl\">$name</a></h2>$description<p class=\"ashop_price\">".__('Price:')." <span class=\"ashop_priceamount\">$price</span></p><p class=\"ashop_moreinfo\"><a href=\"$detailsurl\">".__('More Information...')."</a></p></div>";
			$thisitem++;
		}
	}
	return $productlist;
}
add_shortcode( 'ashopcategory', 'ashopcategory_func' );

function ashopcategoryinfo_func( $atts ) {
	global $ashop_db;
	extract( shortcode_atts( array(
		'id' => '',
		'show' => 'name',
	), $atts ) );

	if (!empty($id) && is_numeric($id)) {
		$result = @mysqli_query($ashop_db, "SELECT name,description FROM category WHERE categoryid='$id'");
		$name = @ashop_mysqli_result($result,0,"name");
		$description = @ashop_mysqli_result($result,0,"description");
	}

	if ($show == "name") return $name;
	else if ($show == "description"); return $description;
}
add_shortcode( 'ashopcategoryinfo', 'ashopcategoryinfo_func' );

function ashopproductname_func( $atts ) {
	global $ashop_db;

	extract( shortcode_atts( array(
		'id' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		return $ashop_product['name'];
	}
}
add_shortcode( 'ashopproductname', 'ashopproductname_func' );

function ashopproductdescription_func( $atts ) {
	global $ashop_db;

	extract( shortcode_atts( array(
		'id' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		return $ashop_product['description'];
	}
}
add_shortcode( 'ashopproductdescription', 'ashopproductdescription_func' );

function ashopproductprice_func( $atts ) {
	global $ashop_db;
	$ashop_path = ashop_get_preference('ashoppath', $ashop_db);
	if (!empty($ashop_path) && file_exists("$ashop_path/admin/ashopconstants.inc.php")) include "$ashop_path/admin/ashopconstants.inc.php";

	extract( shortcode_atts( array(
		'id' => '',
		'attribute' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		// Check for attribute price...
		if (!empty($attribute)) {
			$attributearray = explode(":",$attribute);
			if (!empty($attributearray[0]) && is_numeric($attributearray[0]) && !empty($attributearray[1]) && is_numeric($attributearray[1])) {
				$result = @mysqli_query($ashop_db, "SELECT price FROM parametervalues WHERE parameterid='{$attributearray[0]}' AND valueid='{$attributearray[1]}'");
				$attributeprice = @ashop_mysqli_result($result,0,"price");
				$attributeprices = explode("|",$attributeprices);
				$attributeprice = $attributeprices[0];
			}
		}

		if (empty($attributeprice)) $price = $ashop_product['price'];
		else $price = $attributeprice;
		$pricetext = $ashop_product['pricetext'];
		if (empty($pricetext)) {
			$ashop_currency = ashop_get_preference('ashopcurrency', $ashop_db);
			if (isset($currencysymbols) && is_array($currencysymbols)) $price = $currencysymbols[$ashop_currency]["pre"].$price.$currencysymbols[$ashop_currency]["post"];
		}
		return $price;
	}
}
add_shortcode( 'ashopproductprice', 'ashopproductprice_func' );

function ashopproductlink_func( $atts ) {
	global $ashop_db;

	extract( shortcode_atts( array(
		'id' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		return "<a href=\"{$ashop_product['detailsurl']}\">{$ashop_product['name']}</a>";
	}
}
add_shortcode( 'ashopproductlink', 'ashopproductlink_func' );

function ashopbuylink_func( $atts ) {
	global $ashop_db;
	$ashop_path = ashop_get_preference('ashoppath', $ashop_db);
	$ashop_url = ashop_get_preference('ashopurl', $ashop_db);

	extract( shortcode_atts( array(
		'id' => '',
		'attribute' => '',
		'button' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);
		
		$returnvalue = "<a href=\"{$ashop_url}/buy.php?item={$ashop_product['productid']}&quantity=1&redirect=basket.php";
		if (!empty($attribute)) $returnvalue .= "&attribute=$attribute";
		$returnvalue .= "\">";
		if (!empty($button) && file_exists("$ashop_path/images/buybuttons/$button")) $returnvalue .= "<img src=\"$ashop_url/images/buybuttons/$button\" border=\"0\" alt=\"".__('Buy Now')."\">";
		else $returnvalue .= __('Buy Now');
		$returnvalue .= "</a>";
		return $returnvalue;
	}
}
add_shortcode( 'ashopbuylink', 'ashopbuylink_func' );

function ashopproducturl_func( $atts ) {
	global $ashop_db;

	extract( shortcode_atts( array(
		'id' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		return $ashop_product['detailsurl'];
	}
}
add_shortcode( 'ashopproducturl', 'ashopproducturl_func' );

function ashopwholesaleprice_func( $atts ) {
	global $ashop_db;
	$ashop_path = ashop_get_preference('ashoppath', $ashop_db);
	if (!empty($ashop_path) && file_exists("$ashop_path/admin/ashopconstants.inc.php")) include "$ashop_path/admin/ashopconstants.inc.php";

	extract( shortcode_atts( array(
		'id' => '',
		'attribute' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		// Check for attribute price...
		if (!empty($attribute)) {
			$attributearray = explode(":",$attribute);
			if (!empty($attributearray[0]) && is_numeric($attributearray[0]) && !empty($attributearray[1]) && is_numeric($attributearray[1])) {
				$result = @mysqli_query($ashop_db, "SELECT price FROM parametervalues WHERE parameterid='{$attributearray[0]}' AND valueid='{$attributearray[1]}'");
				$attributeprices = @ashop_mysqli_result($result,0,"price");
				$attributeprices = explode("|",$attributeprices);
				$attributeprice = $attributeprices[1];
			}
		}

		if (empty($attributeprice)) $price = $ashop_product['wholesaleprice'];
		else $price = $attributeprice;
		$ashop_currency = ashop_get_preference('ashopcurrency', $ashop_db);
		$price = $currencysymbols[$ashop_currency]["pre"].$price.$currencysymbols[$ashop_currency]["post"];
		return $price;
	}
}
add_shortcode( 'ashopwholesaleprice', 'ashopwholesaleprice_func' );

function ashopproductsku_func( $atts ) {
	global $ashop_db;

	extract( shortcode_atts( array(
		'id' => '',
		'attribute' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		if (!empty($attribute)) {
			$attributearray = explode(":",$attribute);
			if (!empty($attributearray[0]) && is_numeric($attributearray[0]) && !empty($attributearray[1]) && is_numeric($attributearray[1])) {
				$result = @mysqli_query($ashop_db, "SELECT type,skucode FROM productinventory WHERE productid='$id'");
				while($row = @mysqli_fetch_array($result)) {
					$type = $row["type"];
					$skucode = $row["skucode"];
					$typearray = explode("|",$type);
					if ((is_array($typearray) && in_array($attributearray[1],$typearray)) || $type == $attributearray[1]) $ashop_product['skucode'] = $skucode;
				}
			}
		}

		return $ashop_product['skucode'];
	}
}
add_shortcode( 'ashopproductsku', 'ashopproductsku_func' );

function ashopproductimage_func( $atts ) {
	global $ashop_db, $ashop_product;
	$ashop_path = ashop_get_preference('ashoppath', $ashop_db);
	$ashop_url = ashop_get_preference('ashopurl', $ashop_db);

	extract( shortcode_atts( array(
		'id' => '',
		'size' => '',
	), $atts ) );

	if (is_numeric($id)) {

		$imageinfo = ashop_getproductimages($id,0);
		if (empty($size) || $size == "thumbnail") return "<img src=\"$ashop_url/prodimg/$id/".$imageinfo["thumbnail"]."\" alt=\"{$ashop_product['name']}\">";
		else if ($size == "normal") return "<img src=\"$ashop_url/prodimg/$id/".$imageinfo["product"]."\" alt=\"{$ashop_product['name']}\">";
		else if ($size == "large") return "<img src=\"$ashop_url/prodimg/$id/".$imageinfo["main"]."\" alt=\"{$ashop_product['name']}\">";
	}
}
add_shortcode( 'ashopproductimage', 'ashopproductimage_func' );

function ashopproductinventory_func( $atts ) {
	global $ashop_db;

	extract( shortcode_atts( array(
		'id' => '',
		'attribute' => '',
	), $atts ) );

	if (is_numeric($id)) {
		$ashop_product = ashop_get_productdetails($id, $ashop_db);

		if (!empty($attribute)) {
			$attributearray = explode(":",$attribute);
			if (!empty($attributearray[0]) && is_numeric($attributearray[0]) && !empty($attributearray[1]) && is_numeric($attributearray[1])) {
				$result = @mysqli_query($ashop_db, "SELECT type,inventory FROM productinventory WHERE productid='$id'");
				while($row = @mysqli_fetch_array($result)) {
					$type = $row["type"];
					$inventory = $row["inventory"];
					$typearray = explode("|",$type);
					if ((is_array($typearray) && in_array($attributearray[1],$typearray)) || $type == $attributearray[1]) $ashop_product['inventory'] = $inventory;
				}
			}
		}

		return $ashop_product['inventory'];
	}
}
add_shortcode( 'ashopproductinventory', 'ashopproductinventory_func' );
?>