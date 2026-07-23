<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Second deterministic name pool, used ONLY by the V2-V4 extension cohorts
 * (PDWCHS_Demo_Plan::use_extra_names). Kept fully disjoint from names.php so
 * no two demo customers ever share a full name. Same rule applies: do not
 * reorder existing entries or previously generated datasets change shape.
 */
return array(
	'first' => array(
		'Adrian', 'Bianca', 'Cole', 'Daphne', 'Elliot', 'Farah', 'Gideon',
		'Hazel', 'Idris', 'Josie', 'Kieran', 'Lucia', 'Mason', 'Noor',
		'Otis', 'Petra', 'Quentin', 'Renata', 'Silas', 'Talia', 'Ursula',
		'Vaughn', 'Willa', 'Xavier', 'Yusuf', 'Zora', 'Arlo', 'Brynn',
		'Cedric', 'Delia', 'Emmett', 'Freya', 'Gustav', 'Hattie', 'Ivo',
		'Jolene', 'Klaus', 'Lorna', 'Marek', 'Nell', 'Orla', 'Pierce',
		'Romy', 'Stellan', 'Thea', 'Uri', 'Verna', 'Wesley',
	),
	'last'  => array(
		'Amberson', 'Blackwood', 'Calloway', 'Dresden', 'Eastman', 'Falk',
		'Greenberg', 'Hollis', 'Ibanez', 'Joyner', 'Kessler', 'Lachlan',
		'Merriweather', 'Northcott', 'Oakes', 'Pemberton', 'Quill', 'Radley',
		'Silvers', 'Thackeray', 'Umber', 'Vandermeer', 'Wexford', 'Yarrow',
		'Zeller', 'Ashcroft', 'Bellamy', 'Coyle', 'Draper', 'Ellsworth',
		'Farrow', 'Godfrey', 'Hearst', 'Irwin', 'Jessup', 'Kirby', 'Lassiter',
		'Moreau', 'Nightingale', 'Overton', 'Palmer', 'Quinn', 'Rutledge',
		'Sable', 'Tobin', 'Ulrich', 'Vickers', 'Wren',
	),
);
