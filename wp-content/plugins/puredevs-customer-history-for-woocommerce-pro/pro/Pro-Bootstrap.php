<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pro bootstrap stub.
 *
 * At Batch 0 this class only registers a listener on pdwchs_pro_init.
 * The stub pdwchs_boot() does nothing. Batches 1-5 will add real logic.
 */
class PDWCHS_Pro {

	public function __construct() {
		add_action( 'pdwchs_pro_init', [ $this, 'pdwchs_boot' ] );
	}

	/**
	 * Stub boot method — populated by Batches 1-5.
	 *
	 * @return void
	 */
	public function pdwchs_boot() {
		require_once __DIR__ . '/Helpers/Pro-Helpers.php';
		require_once __DIR__ . '/Helpers/class-pd-customer-history-helper.php';
		require_once __DIR__ . '/Analytics/Revenue/Revenue-Pro.php';
		require_once __DIR__ . '/Analytics/Revenue/Data-Integrity.php';
		new PDWCHS_Revenue_Pro();
		new PDWCHS_Data_Integrity_Ajax();
		require_once __DIR__ . '/Analytics/Products/Product-Revenue-Data.php';
		require_once __DIR__ . '/Analytics/Products/Product-Correlations-Data.php';
		require_once __DIR__ . '/Analytics/Products/Revenue-Patterns-Data.php';
		require_once __DIR__ . '/Analytics/Products/Product-Reports-Ajax.php';
		new PDWCHS_Product_Reports_Ajax();
		require_once __DIR__ . '/Carts/Cart-Cron.php';
		new PDWCHS_Cart_Cron();
		require_once __DIR__ . '/Carts/Cart-Email-Capture.php';
		new PDWCHS_Cart_Email_Capture();
		require_once __DIR__ . '/Carts/Cart-Recovery-Detector.php';
		new PDWCHS_Cart_Recovery_Detector();
		require_once __DIR__ . '/Carts/Cart-Tracker.php';
		new PDWCHS_Cart_Tracker();
		require_once __DIR__ . '/Carts/Cart-Widget.php';
		new PDWCHS_Cart_Widget();
		require_once __DIR__ . '/Carts/Abandoned-Carts-List-Table.php';
		new PDWCHS_Abandoned_Carts_List_Table();

		// Intelligence sub-classes (dependency order)
		require_once __DIR__ . '/Intelligence/Segments-Labels.php';
		require_once __DIR__ . '/Intelligence/RFM.php';
		require_once __DIR__ . '/Intelligence/Health.php';
		require_once __DIR__ . '/Intelligence/Lifecycle.php';
		require_once __DIR__ . '/Intelligence/Behavioral.php';
		require_once __DIR__ . '/Intelligence/Behavioral-Insights.php';
		require_once __DIR__ . '/Intelligence/Churn-Cache.php';
		require_once __DIR__ . '/Intelligence/Churn-Engagement.php';
		require_once __DIR__ . '/Intelligence/Churn-Predictor.php';
		require_once __DIR__ . '/Intelligence/Churn.php';
		require_once __DIR__ . '/Intelligence/Churn-Alerts.php';
		require_once __DIR__ . '/Intelligence/Intelligence.php';
		$intelligence_instance = new PD_Customer_History_Intelligence();
		add_filter( 'pdwchs_intelligence_instance', static function() use ( $intelligence_instance ) {
			return $intelligence_instance;
		} );
		require_once __DIR__ . '/Segments/Pro-Defaults.php';
		new PDWCHS_Pro_Segments_Defaults();
		require_once __DIR__ . '/Segments/Segment-Cap.php';
		new PDWCHS_Segment_Cap();
		require_once __DIR__ . '/Scope-Uncap.php';
		new PDWCHS_Scope_Uncap();
		require_once __DIR__ . '/Analytics/Funnel/Funnel-Data.php';
		require_once __DIR__ . '/Analytics/Funnel/Funnel-Ajax.php';
		new PDWCHS_Funnel_Ajax();
		require_once __DIR__ . '/Stats/Sessions-List-Table.php';
		new PDWCHS_Sessions_List_Table();
		require_once __DIR__ . '/Stats/Taxonomy-Stats-List-Table.php';
		require_once __DIR__ . '/Stats/Stats-List-Table.php';
		new PDWCHS_Taxonomy_Stats_List_Table();
		new PDWCHS_Stats_List_Table();
	}
}
