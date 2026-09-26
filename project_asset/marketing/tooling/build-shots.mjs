// Generates shots.json. Edit here, then: node build-shots.mjs
import fs from 'node:fs';
const A = '/wp-admin/admin.php?page=';
const shots = [];
const add = (s) => shots.push(s);
const co = (...pairs) => pairs.map(([selector, label], i) => ({ n: i + 1, selector, label }));

// ── Navigation ─────────────────────────────────────────────────────────────
add({ id: 'NAV-01', title: 'Plugin menu and top tab bar (Pro)', url: A + 'pd-woo-dashboard', clip: ['#toplevel_page_pd-woo-dashboard', '.nav-tab-wrapper.top-nav'], maxHeight: 520,
  callouts: co(['#toplevel_page_pd-woo-dashboard', 'Customer History admin menu'], ['.nav-tab-wrapper.top-nav', 'Top tab bar on every plugin page'], ['#pd-sessions-tab', 'Sessions tab (Pro)'], ['#pd-stats-tab', 'Statistics tab (Pro)']) });
add({ id: 'NAV-02', title: 'Statistics sub-navigation (Pro)', url: A + 'pd-woo-stats', clip: ['h1', '.subsubsub'], callouts: co(['.subsubsub', 'Statistics sub-navigation: 14 report tabs']) });
add({ id: 'NAV-03', title: 'Sessions sub-navigation (Pro)', url: A + 'pd-woo-sessions', clip: ['h1', '.subsubsub'], callouts: co(['.subsubsub', 'Live Sessions / Live Searches / Trash']) });

// ── Dashboard ──────────────────────────────────────────────────────────────
add({ id: 'DASH-00', title: 'Dashboard, full page (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500 });
const freeWidgets = [
  ['DASH-01', 'Top Customers', true, 'View All → Customers & Users'],
  ['DASH-02', 'Latest Orders', false, 'View All → Order History'],
  ['DASH-03', 'Top Selling Products by This Month', true, 'View All → Top Selling Products (Pro)'],
  ['DASH-04', 'Top Searches', true, 'View All → searches'],
  ['DASH-05', 'Most viewed Product categories', true, 'View All'],
  ['DASH-06', 'Latest Searches', false, 'View All'],
  ['DASH-07', 'Most viewed products', true, 'View All'],
  ['DASH-08', 'Most viewed Brands', true, 'View All'],
];
for (const [id, h, hasChart, viewAll] of freeWidgets) {
  const c = [[`heading:${h}`, `Widget heading "${h}"`]];
  if (hasChart) c.push([`panel:${h} >> [title="Chart View"]`, 'Table / chart toggle']);
  c.push([`panel:${h} >> [title="View All"]`, viewAll], [`panel:${h} >> .toggle-section`, 'Collapse / expand'], [`panel:${h} >> table`, 'Data table']);
  add({ id, title: `Dashboard widget: ${h}`, url: A + 'pd-woo-dashboard', clip: `panel:${h}`, pad: 16, callouts: co(...c) });
  if (hasChart) add({ id: id + '-chart', title: `Dashboard widget: ${h} (chart view)`, url: A + 'pd-woo-dashboard', actions: [{ type: 'click', selector: `panel:${h} >> [title="Chart View"]` }, { type: 'wait', ms: 900 }], clip: `panel:${h}`, pad: 16 });
}
add({ id: 'DASH-10', title: 'Revenue Analytics widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-revenue-widget', pad: 16,
  callouts: co(['heading:Revenue Analytics', 'Heading'], ['#pdwchs-revenue-date-picker', 'Date-range picker (presets)'], ['.pdwchs-data-integrity', 'Data-integrity badge'], ['.pdwchs-revenue-cards-grid', 'Four KPI cards with change vs previous period'], ['.pdwchs-granularity-toggle', 'Daily / Weekly / Monthly'], ['#pdwchs-revenue-chart', 'Revenue Over Time line chart'], ['.pdwchs-revenue-split-grid', 'Revenue by Customer Type: new / returning / guest']) });
// (No DASH-10b: the data-integrity "View Details" panel only renders when a mismatch is detected.)
add({ id: 'DASH-11', title: 'Retention widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-retention-widget', pad: 16,
  callouts: co(['heading:Retention', 'Heading shows the selected window'], ['#pdwchs-retention-preset', 'Window preset: 15d … 24 months'], ['.pdwchs-retention-cards', 'Expected CLV and churn-rate cards'], ['#pdwchs-retention-chart', 'Churn rate per period'], ['.pdwchs-retention-series-table', 'Period / Base / Churned / Rate table']) });
add({ id: 'DASH-12', title: 'Lifetime value distribution widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-ltv-widget', pad: 16,
  callouts: co(['heading:Lifetime value distribution', 'Heading'], ['.pdwchs-ltv-cards', 'Average and median LTV'], ['.pdwchs-ltv-total', 'Customers counted'], ['#pdwchs-ltv-chart', 'Histogram by spend bracket'], ['.pdwchs-ltv-bracket-table', 'Bracket / Customers / Share table']) });
add({ id: 'DASH-13', title: 'Abandoned Carts widget (Pro)', url: A + 'pd-woo-dashboard', clip: 'panel:Abandoned Carts (Last 7 Days)', pad: 16,
  callouts: co(['heading:Abandoned Carts (Last 7 Days)', 'Heading'], ['.pdwchs-cart-widget-headline', 'Count and value at risk'], ['.pdwchs-cart-widget-note', 'Observation-only note'], ['.pdwchs-cart-widget a', 'Link to the Abandoned Carts page']) });
add({ id: 'DASH-14', title: 'Customer Segments (RFM Analysis) widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-rfm-widget', pad: 16,
  callouts: co(['heading:Customer Segments (RFM Analysis)', 'Heading'], ['.pdwchs-refresh-rfm', 'Refresh'], ['#pdwchs-rfm-chart', 'Doughnut of segment share'], ['heading:Segments', 'Clickable legend (filters the customer list)'], ['.pdwchs-rfm-footer', 'Last updated stamp']) });
add({ id: 'DASH-15', title: 'Customer Health Distribution widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-health-distribution-widget', pad: 16,
  callouts: co(['heading:Customer Health Distribution', 'Heading'], ['.pdwchs-refresh-health', 'Refresh'], ['#pdwchs-health-distribution-chart', 'One bar per band: Excellent … Critical']) });
add({ id: 'DASH-16', title: 'Declining Health Alert widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-declining-health-widget', pad: 16,
  callouts: co(['heading:Declining Health Alert', 'Heading'], ['#pdwchs-declining-health-list', 'Customers with a 10+ point drop']) });
add({ id: 'DASH-17', title: 'Customer Lifecycle Funnel widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '#pdwchs-lifecycle-funnel-widget', pad: 16,
  callouts: co(['heading:Customer Lifecycle Funnel', 'Heading'], ['.pdwchs-funnel', 'Prospect → New → Active → Loyal (click a stage to filter)'], ['.pdwchs-engagement-issues', 'At Risk and Churned counts'], ['#pdwchs-lifecycle-funnel-widget .pdwchs-widget-footer', 'Total customers tracked']) });
add({ id: 'DASH-18', title: 'At Risk Customers (30+ Days) widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '#pdwchs-at-risk-alert-widget', pad: 16,
  callouts: co(['heading:At Risk Customers (30+ Days)', 'Heading'], ['#pdwchs-at-risk-alert-widget .pdwchs-widget-content', 'Customers in the At Risk stage for 30+ days'], ['#pdwchs-at-risk-alert-widget .pdwchs-widget-footer', 'View All At Risk Customers']) });
add({ id: 'DASH-19', title: 'Behavioral Segments widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 3000, clip: '.pdwchs-behavioral-distribution-widget', pad: 16,
  callouts: co(['heading:Behavioral Segments', 'Heading'], ['#pdwchs-behavioral-segments-chart', 'Customers per behavioural tag'], ['heading:Actionable Insights', 'Insights list']) });
add({ id: 'DASH-20', title: 'Churn Risk Overview widget (Pro)', url: A + 'pd-woo-dashboard', settleMs: 2500, clip: '.pdwchs-churn-dashboard-widget', pad: 16,
  callouts: co(['heading:Churn Risk Overview', 'Heading'], ['.pdwchs-churn-stats-grid', 'Critical / High / Medium / Active Alerts'], ['heading:Top At-Risk Customers', 'Top 5 by risk score'], ['heading:Active Alerts', 'Latest alerts with priority'], ['.pdwchs-churn-widget-footer', 'Link to Churn Prediction page']) });
add({ id: 'DASH-21', title: 'RFM matrix widget (Pro)', url: A + 'pd-woo-dashboard', clip: '.pdwchs-rfm-matrix-widget', pad: 16,
  callouts: co(['heading:RFM matrix', 'Heading'], ['.pdwchs-rfm-matrix-intro', 'How to read the grid'], ['.pdwchs-rfm-matrix-scroll', 'Recency (rows) × frequency (columns), count per cell'], ['.pdwchs-rfm-matrix-calculated', 'Scores last calculated']) });

// ── Customers & Users ──────────────────────────────────────────────────────
add({ id: 'CUST-01', title: 'Customers & Users, full page (Pro)', url: A + 'pd-woo-customers', maxHeight: 2400,
  callouts: co(['h1', 'Page title'], ['fieldset', 'Advanced search filters'], ['a[href*="download_cus_info"]', 'Download CSV'], ['form.search-box', 'Keyword search (Pro)'], ['#show-items', 'Rows per page (Pro)'], ['table.wp-list-table', 'Customer list']) });
add({ id: 'CUST-02', title: 'Customer filters (Pro shows every filter)', url: A + 'pd-woo-customers', clip: 'fieldset', pad: 16,
  callouts: co(['fieldset select', 'Role (Pro)'], ['#customer_country + .select2', 'Country (Pro)'], ['#refund_rate', 'Refund Rate (Pro)'], ['#rfm_segment + .select2', 'RFM Segment (Pro)'], ['#health_score_range', 'Health Score (Pro)'], ['#health_trend', 'Health Trend (Pro)'], ['#behavior_filter', 'Behavior (Pro)'], ['#tag_filter', 'Manual Tag (Pro)'], ['#product_filter + .select2', 'Product Purchased'], ['#pdwchs-save-segment-btn', 'Save as Segment']) });
add({ id: 'CUST-03', title: 'Save as Segment modal', url: A + 'pd-woo-customers&order_number=greater_then&order_number_input=2', actions: [{ type: 'click', selector: '#pdwchs-save-segment-btn' }, { type: 'wait', ms: 600 }], clip: '#pdwchs-modal-overlay .pdwchs-modal, #pdwchs-modal-overlay > div', pad: 30,
  callouts: co(['#pdwchs-segment-name', 'Segment name'], ['#pdwchs-segment-description', 'Description (optional)'], ['#pdwchs-modal-overlay .button-primary', 'Save Segment']) });
add({ id: 'CUST-04', title: 'Customer list columns (Pro adds six)', url: A + 'pd-woo-customers', clip: 'table.wp-list-table', maxHeight: 520, pad: 12,
  callouts: co(['th#rfm_segment', 'RFM Segment'], ['th#health_score', 'Health Score'], ['th#lifecycle_stage', 'Lifecycle Stage'], ['th#behavior_tags', 'Behaviors'], ['th#refund_rate', 'Refund Rate'], ['th#order_gap', 'Avg Order Gap'], ['th#actions', 'View / Email actions']) });
add({ id: 'CUST-05', title: 'List controls: keyword search, rows per page, lifecycle filters (Pro)', url: A + 'pd-woo-customers', clip: ['form.search-box', '#show-result', '.pdwchs-lifecycle-filters'], pad: 16,
  callouts: co(['form.search-box', 'Search by name or email'], ['#show-items', 'Show N entries'], ['#lifecycle_stage_filter', 'Lifecycle stage'], ['#lifecycle_days_filter', 'Days in stage']) });

// ── Customer profile (hero customer) ───────────────────────────────────────
const PROF = A + 'pd-woo-customer&user_id={HERO}';
add({ id: 'PROF-00', title: 'Customer Details, full page (Pro)', url: PROF, maxHeight: 3000 });
add({ id: 'PROF-01', title: 'Order history grouped by status', url: PROF, clip: 'panel:Completed Orders', maxHeight: 620, pad: 16,
  callouts: co(['heading:Completed Orders', 'One collapsible group per order status'], ['panel:Completed Orders >> .toggle-section', 'Collapse / expand'], ['panel:Completed Orders >> table', 'Order / Date / Items / Total / Actions']) });
add({ id: 'PROF-02', title: 'Activity Timeline (Pro)', url: PROF, clip: '.pdwchs-timeline-panel', maxHeight: 760, pad: 16,
  callouts: co(['heading:Activity Timeline', 'Heading with Search History / All Sessions links'], ['.pdwchs-timeline-filters', 'All / Searched / Viewed / Carted / Ordered'], ['.pdwchs-timeline-list', 'Chronological events'], ['.pdwchs-timeline-more', 'Load older']) });
add({ id: 'PROF-02b', title: 'Activity Timeline filtered to Ordered (Pro)', url: PROF, actions: [{ type: 'click', selector: 'button.pdwchs-timeline-filter:has-text("Ordered")' }, { type: 'wait', ms: 1200 }], clip: '.pdwchs-timeline-panel', maxHeight: 760, pad: 16 });
add({ id: 'PROF-03', title: 'Financial Summary', url: PROF, clip: 'panel:Financial Summary', pad: 16, callouts: co(['heading:Financial Summary', 'Seven totals for this customer']) });
add({ id: 'PROF-04', title: 'Personal Information', url: PROF, clip: 'panel:Personal Information', pad: 16, callouts: co(['heading:Personal Information', 'Identity and contact'], ['panel:Personal Information >> a[href*="user-edit"]', 'Edit this user']) });
add({ id: 'PROF-05', title: 'Notes', url: PROF, clip: '.pdwchs-notes-panel', pad: 16,
  callouts: co(['heading:Notes', 'Heading with live count'], ['.pdwchs-notes-add', 'Compose a private note'], ['.pdwchs-note-add-btn', 'Add Note'], ['.pdwchs-notes-list', 'Newest first, author and date'], ['.pdwchs-note-delete', 'Delete']) });
add({ id: 'PROF-06', title: 'RFM Analysis card (Pro)', url: PROF, clip: '.pdwchs-rfm-analysis-card', pad: 16,
  callouts: co(['heading:RFM Analysis', 'Heading'], ['.pdwchs-rfm-card-header .pdwchs-badge', 'Segment badge'], ['.pdwchs-rfm-scores', 'R / F / M scores out of 5'], ['.pdwchs-rfm-recommendations', 'Recommended Actions'], ['.pdwchs-recalculate-rfm-btn', 'Recalculate Now']) });
add({ id: 'PROF-07', title: 'Customer Health Score card (Pro)', url: PROF, clip: '.pdwchs-health-score-card', pad: 16,
  callouts: co(['heading:Customer Health Score', 'Heading with trend chip'], ['.pdwchs-health-gauge-container', '0–100 gauge'], ['.pdwchs-health-breakdown', 'Score Breakdown: four components'], ['.pdwchs-health-interpretation', 'Health Interpretation'], ['.pdwchs-recalculate-health', 'Recalculate Now']) });
add({ id: 'PROF-08', title: 'Customer Lifecycle card (Pro)', url: PROF, clip: '.pdwchs-lifecycle-card', pad: 16,
  callouts: co(['heading:Customer Lifecycle', 'Heading'], ['heading:Time in Current Stage:', 'Days in stage'], ['heading:Customer Journey:', 'Stage history'], ['.pdwchs-recalculate-lifecycle', 'Recalculate Now']) });
add({ id: 'PROF-09', title: 'Behavioral Profile card (Pro)', url: PROF, clip: '.pdwchs-behavioral-card', pad: 16,
  callouts: co(['heading:Behavioral Profile', 'Heading with tag count'], ['.pdwchs-behavioral-tags', 'Detected tags with confidence %'], ['.pdwchs-next-action', 'Next Best Action'], ['.pdwchs-preferences', 'Shopping Preferences'], ['.pdwchs-refresh-behavioral-btn', 'Refresh Analysis']) });
add({ id: 'PROF-10', title: 'Churn Risk Analysis card (Pro)', url: PROF, clip: '.pdwchs-churn-card', pad: 16,
  callouts: co(['heading:Churn Risk Analysis', 'Heading'], ['.pdwchs-churn-score-section', 'Risk score ring, level, confidence, trend'], ['.pdwchs-churn-components', 'Risk Components'], ['.pdwchs-churn-indicators', 'Key Indicators'], ['.pdwchs-recalculate-churn', 'Recalculate']) });
add({ id: 'PROF-11', title: 'Manual Tags panel (Pro)', url: PROF, clip: '.pdwchs-tags-panel', pad: 16,
  callouts: co(['heading:Manual Tags', 'Heading with count'], ['.pdwchs-tags-hint', 'Manual vs automatic tags note'], ['.pdwchs-tags-add', 'Add or pick a tag'], ['.pdwchs-tags-list', 'Assigned tags, × to remove']) });

// ── Other free pages ───────────────────────────────────────────────────────
add({ id: 'MAIL-01', title: 'Send Email to Customer', url: A + 'pd-woo-customer-email&user_id={HERO}', maxHeight: 1400,
  callouts: co(['heading:Email Form', 'Compose form'], ['#email_subject, input[name="email_subject"], input[name="subject"]', 'Subject'], ['.form-table .wp-editor-wrap, .form-table textarea', 'Message editor'], ['input[type="submit"].button-primary, button.button-primary', 'Send Email'], ['heading:Customer Information', 'Customer sidebar']) });
add({ id: 'ORD-01', title: 'Order History (Pro filters visible)', url: A + 'pd-woo-order-history', maxHeight: 2000,
  callouts: co(['#daterange', 'Date range'], ['heading:Total Amount', 'Total Amount (Pro)'], ['heading:Payment Method', 'Payment Method (Pro)'], ['heading:Order Status', 'Order Status (Pro)'], ['form.search-box', 'Keyword search (Pro)'], ['heading:Last Orders', 'Order table']) });
add({ id: 'REV-01', title: 'Revenue page', url: A + 'pd-woo-revenue',
  callouts: co(['#pd-customer-history form', 'From / To / Apply'], ['#pd-customer-history form + div, .pd-woo-panel:has(#pdwchs-revenue-chart)', 'KPI cards'], ['heading:Net revenue over time', 'Trend chart'], ['#pdwchs-revenue-chart', 'Daily net revenue']) });
add({ id: 'SEG-01', title: 'Customer Segments page (Pro)', url: A + 'pd-woo-segments', maxHeight: 1500,
  callouts: co(['heading:Saved Segments', 'Saved Segments panel'], ['panel:Saved Segments >> a', '+ New Segment → Customers page'], ['table.wp-list-table', 'Segment / Description / Customers / Created By / Created / Actions'], ['.pdwchs-segment-actions', 'Load · export preset · Export CSV · Delete']) });
add({ id: 'SEG-02', title: 'Segment row actions (Pro export presets)', url: A + 'pd-woo-segments', clip: 'table.wp-list-table', maxHeight: 560, pad: 12,
  callouts: co(['.pdwchs-load-segment', 'Load into the Customers page'], ['.pdwchs-export-preset', 'Standard / Klaviyo / Mailchimp preset (Pro)'], ['.pdwchs-export-segment', 'Export CSV'], ['.pdwchs-delete-segment', 'Delete (not on Default segments)']) });

// ── Sessions & Statistics (Pro) ────────────────────────────────────────────
const listPage = (id, title, slug, extra = []) => add({ id, title, url: A + slug, maxHeight: 1500, callouts: co(['h1', 'Page title'], ['.subsubsub', 'Sub-navigation'], ...extra, ['table.wp-list-table', 'Report table']) });
listPage('SESS-01', 'Live Sessions (Pro)', 'pd-woo-sessions', [['#pd-customer-history a.button', 'Check for new Sessions'], ['form.search-box', 'Date range + keyword search'], ['#show-items', 'Rows per page']]);
listPage('SESS-02', 'Live Searches (Pro)', 'pd-woo-searches', [['form.search-box', 'Date range + keyword search']]);
listPage('SESS-03', 'Trash (Pro)', 'pd-woo-trash');
listPage('STAT-01', 'Statistics: Site Pages (Pro)', 'pd-woo-stats', [['form.search-box', 'Keyword search'], ['#show-items', 'Rows per page']]);
listPage('STAT-02', 'Statistics: Keywords (Pro)', 'pd-woo-search-stats', [['form.search-box', 'Keyword search']]);
listPage('STAT-03', 'Statistics: Zero-Result Searches (Pro)', 'pd-woo-zero-result-searches', [['form.search-box', 'Search terms']]);
listPage('STAT-04', 'Statistics: Viewed vs Purchased Ratio (Pro)', 'pd-woo-product-view-stats', [['form.search-box', 'Product search']]);
listPage('STAT-05', 'Statistics: Top Selling Products by This Month (Pro)', 'pd-woo-top-selling-stats');
add({ id: 'STAT-06', title: 'Statistics: Product Revenue & Comparison (Pro)', url: A + 'pd-woo-product-revenue', actions: [{ type: 'select', selector: '#pdwchs-product-select', value: ['14', '22', '20'] }, { type: 'click', selector: '#pdwchs-product-apply' }, { type: 'wait', ms: 2500 }], maxHeight: 1400,
  callouts: co(['.pdwchs-product-revenue-filters', 'Products (max 3) · Date Range · Granularity · Apply'], ['#pdwchs-product-revenue-chart', 'One line per product'], ['#pdwchs-product-summary-cards', 'Summary cards']) });
add({ id: 'STAT-07', title: 'Statistics: Aggregate Conversion Funnel (Pro)', url: A + 'pd-woo-conversion-funnel-stats', settleMs: 2500, maxHeight: 2800,
  callouts: co(['.pdwchs-funnel-filters', 'Date Range · Granularity · Apply'], ['#pdwchs-funnel-bar-chart', 'Visitors → Add to Cart → Checkout Started → Purchased'], ['#pdwchs-funnel-stat-cards', 'Stage-to-stage conversion rates'], ['heading:Funnel Over Time', 'Funnel Over Time'], ['heading:Conversion Funnel Visualization', 'Per-product session funnel (Last N days)'], ['#num-of-days', 'Num of Days']) });
add({ id: 'STAT-08', title: 'Statistics: Cohort Retention, customers view (Pro)', url: A + 'pd-woo-cohorts', maxHeight: 1400,
  callouts: co(['.pdwchs-cohort-view-toggle', 'Customers / Revenue view'], ['.pdwchs-cohort-export-form', 'Export CSV'], ['#pdwchs-cohort-chart', 'Average retention by month since first order'], ['.pdwchs-cohort-table', 'Cohort × M+0 … M+12 heat-map']) });
add({ id: 'STAT-08b', title: 'Statistics: Cohort Retention, revenue view (Pro)', url: A + 'pd-woo-cohorts&view=revenue', clip: '.pdwchs-cohort-table-wrap', pad: 16 });
add({ id: 'STAT-09', title: 'Statistics: Frequently Bought Together (Pro)', url: A + 'pd-woo-product-correlations', actions: [{ type: 'select', selector: '#pdwchs-anchor-product', value: '14' }, { type: 'wait', ms: 2500 }], maxHeight: 1200,
  callouts: co(['#pdwchs-anchor-product', 'Anchor Product'], ['.pdwchs-correlations-filter-row .pdwchs-filter-group', 'Window: 30 / 60 / 90 / 180 days'], ['.pdwchs-correlations-filter-row .pdwchs-filter-group:nth-of-type(2), .pdwchs-correlations-filter-row .pdwchs-filter-group + .pdwchs-filter-group', 'Show top 5 / 10 / 20'], ['.pdwchs-correlations-table', 'Rank / Product / Co-occurrences / Confidence']) });
add({ id: 'STAT-10', title: 'Statistics: Geographic Performance (Pro)', url: A + 'pd-woo-geographic', settleMs: 2000, maxHeight: 1500,
  callouts: co(['.pdwchs-geo-range-form', 'Date range + Apply'], ['.pdwchs-geo-summary', 'Net sales · Orders · Items · Countries'], ['heading:Compare countries', 'Compare countries'], ['#pdwchs-geo-compare-a', 'Country A'], ['#pdwchs-geo-compare-chart', 'Comparison chart'], ['.pdwchs-geo-table-wrap', 'Sortable country table']) });
listPage('STAT-11', 'Statistics: Category View (Pro)', 'pd-woo-category-stats&pd_taxonomy=product_cat');
listPage('STAT-11b', 'Statistics: Brand View (Pro)', 'pd-woo-brand-stats&pd_taxonomy=product_brand');
for (const [suffix, view] of [['', 'Day of Week'], ['b', 'Hour of Day'], ['c', 'Heatmap']]) {
  add({ id: 'STAT-12' + suffix, title: `Statistics: Revenue Patterns, ${view} (Pro)`, url: A + 'pd-woo-revenue-patterns', actions: [{ type: 'click', selector: `button.pdwchs-view-btn:has-text("${view}")` }, { type: 'click', selector: '#pdwchs-patterns-apply' }, { type: 'wait', ms: 2500 }], maxHeight: 1300,
    callouts: suffix ? [] : co(['.pdwchs-product-revenue-filters', 'Date Range · View · Apply'], ['#pdwchs-patterns-summary', 'Best / worst day and hour'], ['#pdwchs-patterns-dow-chart', 'Revenue by day of week']) });
}
listPage('STAT-13', 'Statistics: Total Spent (Pro)', 'pd-woo-stats-spent', [['#show-items', 'Rows per page']]);

// ── Abandoned Carts, Churn, Tags (Pro) ─────────────────────────────────────
add({ id: 'CART-01', title: 'Abandoned Carts list (Pro)', url: A + 'pd-woo-abandoned-carts', maxHeight: 1500,
  callouts: co(['.pdwchs-carts-filter-form', 'Status · Date range · Min total · Filter'], ['table.wp-list-table', 'Customer / Items / Total / Status / Last Updated / Actions']) });
add({ id: 'CART-02', title: 'Cart Detail (Pro)', url: A + 'pd-woo-abandoned-carts&cart_id={CART}', maxHeight: 1100,
  callouts: co(['heading:Cart Detail', 'Heading with status chip'], ['.pdwchs-cart-detail-main .pdwchs-cart-detail-meta-box:first-child', 'Customer or guest details'], ['.pdwchs-cart-detail-main .pdwchs-cart-detail-meta-box:last-child', 'Cart Contents'], ['.pdwchs-cart-detail-sidebar .pdwchs-cart-detail-meta-box', 'Status Timeline']) });
add({ id: 'CHURN-01', title: 'Churn Prediction: At-Risk Customers tab (Pro)', url: A + 'pd-woo-churn-prediction', maxHeight: 1500,
  callouts: co(['.pdwchs-churn-stats-grid', 'Critical / High / Medium / Active Alerts'], ['.nav-tab-wrapper:not(.top-nav)', 'At-Risk · Alerts · Analytics · Settings'], ['#pdwchs-risk-filter', 'Risk level filter'], ['#pdwchs-sort-filter', 'Sort'], ['#pdwchs-tab-at-risk table', 'At-risk table with score, indicator, value, last order']) });
add({ id: 'CHURN-02', title: 'Churn Prediction: Alerts tab (Pro)', url: A + 'pd-woo-churn-prediction&tab=alerts', maxHeight: 1500,
  callouts: co(['heading:Churn Alerts', 'Heading'], ['.pdwchs-alert-summary', 'New / In Progress / Resolved'], ['.pdwchs-alert-status-select', 'Status (inline)'], ['.pdwchs-alert-assign-select', 'Assigned To (inline)']) });
add({ id: 'CHURN-03', title: 'Churn Prediction: Analytics tab (Pro)', url: A + 'pd-woo-churn-prediction&tab=analytics', settleMs: 2000, maxHeight: 1400,
  callouts: co(['heading:Risk Distribution', 'Risk Distribution doughnut'], ['heading:Alert Statistics (30 Days)', 'Alert Statistics'], ['heading:Customer Risk Summary', 'Stacked risk bar']) });
add({ id: 'CHURN-04', title: 'Churn Prediction: Settings tab (Pro)', url: A + 'pd-woo-churn-prediction&tab=settings', maxHeight: 2000,
  callouts: co(['heading:Risk Level Thresholds', 'Risk Level Thresholds'], ['heading:Scoring Weights', 'Scoring Weights (must total 100)'], ['heading:Alert Settings', 'Alert Settings (admin email only)'], ['heading:General Settings', 'Minimum Orders'], ['#pdwchs-save-churn-settings', 'Save Settings']) });
add({ id: 'TAGS-01', title: 'Manual Tags page (Pro)', url: A + 'pd-woo-manual-tags',
  callouts: co(['.pdwchs-tags-create', 'Create a tag'], ['table.wp-list-table', 'Name / Slug / Customers / Created / Actions'], ['.pdwchs-tag-rename-btn', 'Rename'], ['.pdwchs-tag-delete-btn', 'Delete']) });

// ── Settings ───────────────────────────────────────────────────────────────
add({ id: 'SET-00', title: 'Settings, full page (Pro)', url: A + 'pd-woo-settings' });
add({ id: 'SET-01', title: 'General Settings card', url: A + 'pd-woo-settings', clip: 'panel:General Settings', pad: 16,
  callouts: co(['#results_per_page', 'Results per page'], ['#show_bot_sessions', 'Show BOT sessions (Pro)'], ['#default_save_admin_session', 'Save admin sessions (Pro)'], ['#hide_users_with_no_orders', 'Hide users with no orders']) });
add({ id: 'SET-02', title: 'Reports card (digest)', url: A + 'pd-woo-settings', clip: 'panel:Reports', pad: 16,
  callouts: co(['#order', 'Order (free)'], ['#top_customer', 'Top customers (free)'], ['#search', 'Search (Pro)'], ['#most_viewed_products', 'Most viewed products (Pro)'], ['#most_viewed_categories', 'Most viewed categories (Pro)'], ['#digest_interval', 'Weekly / Monthly'], ['#send_weekly_report_to', 'Recipient']) });
add({ id: 'SET-03', title: 'Abandoned Cart Tracking card (Pro)', url: A + 'pd-woo-settings', clip: '.cart-settings-form', pad: 16,
  callouts: co(['#pdwchs_cart_tracking_enabled', 'Cart tracking'], ['#pdwchs_cart_email_capture_enabled', 'Email early capture'], ['#pdwchs_cart_abandonment_timeout_minutes', 'Abandonment timeout'], ['#pdwchs_cart_cleanup_days', 'Data retention']) });
add({ id: 'SET-04', title: 'Digest Segment Sheet card (Pro)', url: A + 'pd-woo-settings', clip: '.digest-segment-settings-form', pad: 16,
  callouts: co(['#pdwchs_digest_segment_enabled', 'Attach a segment CSV'], ['#pdwchs_digest_segment_id', 'Segment']) });
add({ id: 'SET-05', title: 'Session data tools: Export / Import / Empty Tables (Pro)', url: A + 'pd-woo-settings', clip: ['heading:Export', 'panel:Empty Tables'], pad: 16,
  callouts: co(['heading:Export', 'Export all session data'], ['heading:Import', 'Import CSV'], ['heading:Empty Tables', 'Custom Delete by date range / Delete All']) });

// ── WooCommerce & WordPress surfaces ───────────────────────────────────────
add({ id: 'WC-01', title: 'WooCommerce order screen: Customer Intelligence meta box (Pro)', url: A + 'wc-orders&action=edit&id={HERO_ORDER}', clip: '#pdwchs_order_intelligence', pad: 24,
  callouts: co(['#pdwchs_order_intelligence h2', 'Customer Intelligence meta box'], ['#pdwchs_order_intelligence .inside', 'Health · RFM · Lifecycle · LTV · Last seen · Predicted next order'], ['#pdwchs_order_intelligence a', 'View full profile']) });
add({ id: 'WC-01b', title: 'WooCommerce order screen with the meta box in context (Pro)', url: A + 'wc-orders&action=edit&id={HERO_ORDER}', maxHeight: 1500, callouts: co(['#pdwchs_order_intelligence', 'Customer Intelligence meta box in the sidebar']) });
add({ id: 'SYS-01', title: 'Site Health → Info → PureDevs Customer History (local telemetry)', url: '/wp-admin/site-health.php?tab=debug', actions: [{ type: 'click', selector: '#health-check-section-pdwchs_customer_history' }, { type: 'wait', ms: 600 }], clip: '#health-check-accordion-block-pdwchs_customer_history, #health-check-section-pdwchs_customer_history', pad: 20,
  callouts: co(['#health-check-section-pdwchs_customer_history', 'Section'], ['#health-check-accordion-block-pdwchs_customer_history table', 'Four read-only fields; nothing leaves the server']) });
add({ id: 'SYS-02', title: 'Tools → Export Personal Data', url: '/wp-admin/export-personal-data.php', maxHeight: 900, callouts: co(['h1', 'WordPress privacy tools — the plugin registers exporters and erasers for notes, tags and carts']) });

// ── Free tier ──────────────────────────────────────────────────────────────
const F = { tier: 'free' };
add({ ...F, id: 'FREE-00', title: 'Dashboard, full page (Free)', url: A + 'pd-woo-dashboard' });
add({ ...F, id: 'FREE-01', title: 'Free dashboard: six widgets, trial notice and the attention banner', url: A + 'pd-woo-dashboard', maxHeight: 1900,
  callouts: co(['.nav-tab-wrapper.top-nav', 'Free tab bar: Search Analytics replaces Sessions/Statistics'], ['.notice.notice-info:has(a[href*="pdwchs_dismiss"]), .pd-woo-dashboard .notice', 'Notice area: trial notice, with the upsell banner directly below']) });
add({ ...F, id: 'FREE-02', title: 'Trial notice (premium build without an active licence)', url: A + 'pd-woo-dashboard', clip: '#wpbody-content .notice', pad: 16, callouts: co(['#wpbody-content .notice', 'Freemius trial notice (Free build without an active licence)']) });
add({ ...F, id: 'FREE-03', title: 'Customers list (Free): scope banner and free-only filters', url: A + 'pd-woo-customers', maxHeight: 1600,
  callouts: co(['fieldset', 'Free filters: orders, total spent, product purchased'], ['#pd-customer-history .notice', '100-customer scope banner'], ['table.wp-list-table', 'Free columns only']) });
add({ ...F, id: 'FREE-04', title: 'Customer profile (Free): locked Pro previews', url: PROF, maxHeight: 2600,
  callouts: co(['heading:Full Session History', 'Locked: Full Session History'], ['heading:RFM Segment', 'Locked: RFM Segment'], ['heading:Customer Health Score', 'Locked: Health Score'], ['heading:Churn Risk', 'Locked: Churn Risk'], ['heading:Behavioral Tags', 'Locked: Behavioral Tags'], ['heading:Lifecycle Stage', 'Locked: Lifecycle Stage']) });
add({ ...F, id: 'FREE-04b', title: 'Locked card close-up (Free)', url: PROF, clip: 'panel:RFM Segment', pad: 16, callouts: co(['heading:RFM Segment', 'Title'], ['panel:RFM Segment >> a', 'Unlock with Pro']) });
add({ ...F, id: 'FREE-05', title: 'Segments (Free): one custom segment and the Ready-made Pro segments teaser', url: A + 'pd-woo-segments', maxHeight: 1500,
  callouts: co(['heading:Saved Segments', 'Saved Segments (1 custom on Free)'], ['heading:Ready-made Pro segments', 'Locked teaser table'], ['panel:Ready-made Pro segments >> a', 'Unlock with Pro']) });
add({ ...F, id: 'FREE-06', title: 'Segment-cap modal (Free)', url: A + 'pd-woo-customers&order_number=greater_then&order_number_input=2', actions: [{ type: 'click', selector: '#pdwchs-save-segment-btn' }, { type: 'wait', ms: 500 }, { type: 'fill', selector: '#pdwchs-segment-name', value: 'Second segment' }, { type: 'click', selector: '#pdwchs-modal-overlay .button-primary' }, { type: 'waitFor', selector: '.pdwchs-upsell-box' }, { type: 'wait', ms: 500 }], clip: '.pdwchs-upsell-box', pad: 40, callouts: co(['.pdwchs-upsell-box h2', 'Unlock unlimited segments with Pro'], ['.pdwchs-upsell-box .button-primary', 'Upgrade to Pro'], ['.pdwchs-upsell-close', 'Maybe later']) });
add({ ...F, id: 'FREE-07', title: 'Search Analytics page (Free only)', url: A + 'pd-woo-search-analytics', maxHeight: 2600,
  callouts: co(['#pd-customer-history .notice', 'Heads-up notice about admin activity'], ['heading:Top Keyword Searches', 'Top Keyword Searches'], ['heading:Last Searches', 'Last Searches'], ['heading:Zero-Result Searches', 'Zero-Result Searches'], ['heading:Most Viewed Products', 'Most Viewed Products']) });
add({ ...F, id: 'FREE-08', title: 'Revenue page (Free): 90-day floor on the From date', url: A + 'pd-woo-revenue', callouts: co(['#pd-customer-history form input[type="date"]', 'From date cannot go earlier than 90 days back'], ['heading:Net revenue over time', 'Trend chart']) });
add({ ...F, id: 'FREE-09', title: 'Settings (Free)', url: A + 'pd-woo-settings', callouts: co(['panel:General Settings', 'Two free fields'], ['panel:Reports', 'Order + Top customers, Weekly/Monthly, recipient']) });
add({ ...F, id: 'FREE-10', title: 'Order History (Free): date range only', url: A + 'pd-woo-order-history', maxHeight: 1400, callouts: co(['#daterange', 'Date range (90-day floor)'], ['table.wp-list-table', 'Order list']) });

fs.writeFileSync(new URL('./shots.json', import.meta.url), JSON.stringify(shots, null, 2) + '\n');
console.log(`${shots.length} shots written`);
