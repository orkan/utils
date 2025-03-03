<?php
require dirname( __DIR__, 2 ) . '/tools/composer/autoload.php';

use Orkan\Input;
use Orkan\Inputs;

// Save POST data to file, then use these values to render inputs.
define( 'JSON', __DIR__ . '/demo-input.json' );
touch( JSON );

$fields = require __DIR__ . '/demo-input.cfg.php';
Input::fieldsPrepare( $fields, true );

if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
	define( 'RESET', isset( $_POST['reset'] ) );

	/* @formatter:off */
	$cookie = [
		'expires' => RESET ? 1 : strtotime( '+1 year' ),
		'path'    => $_SERVER['PHP_SELF'],
	];
	/* @formatter:on */

	switch ( $_GET['action'] )
	{
		// Inputs: selector
		case 'filter':
			foreach ( array_keys( $fields ) as $name ) {
				/* @formatter:off */
				setcookie( "demo[selector][$name]", 'on', array_merge( $cookie, [
					'expires' => isset( $_POST[$name] ) ? $cookie['expires'] : 1, // remove cookie if not in POST
				]));
				/* @formatter:on */
			}
			header( 'Location: ' . $_SERVER['PHP_SELF'] );
			exit();

		// Inputs: demo
		case 'save':
			$options = RESET ? [] : $_POST;
			file_put_contents( JSON, json_encode( $options, JSON_PRETTY_PRINT ) );
			break;
	}

	if ( RESET ) {
		// Remove cookies of renamed/deleted fields
		$orphaned = array_diff_key( $_COOKIE['demo']['selector'] ?? [], $fields );
		foreach ( array_keys( $orphaned ) as $name ) {
			setcookie( "demo[selector][$name]", 'deleted', $cookie );
		}
		header( 'Location: ' . $_SERVER['PHP_SELF'] );
		exit();
	}
}

?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo $title = '[DEMO] Orkan\Input' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="demo.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->
</head>
<body class="container-md">
	<h1 class="display-6"><?php printf( '<a href="%s">%s</a>', $_SERVER['PHP_SELF'], $title )  ?></h1>

	<?php
	$checked = $_COOKIE['demo']['selector'] ?? [];
	$options = json_decode( file_get_contents( JSON ), true ) ?: [];
	define( 'DEBUG', isset( $options['debug'] ) );
	define( 'VERBOSE', isset( $options['verbose'] ) );

	// Dn NOT display selector in DEBUG mode, so we'll get the first new Input() from FORM fields.
	if ( !DEBUG ) {
		/* @formatter:off */
		$selector = [ 'type' => 'group' ];
		foreach ( array_keys( $fields ) as $name ) {
			$selector['items'][$name] = [
				'type'  => 'checkbox',
				'idfix' => '-selector', // fix collisions with generated $field input ids in admin table
				'tag'   => $name,
			];
		}
		$Selector = new Input( $selector, $checked );
		$Filter = new Input([
			'type'   => 'radio',
			'name'   => 'filter',
			'value'  => -1,
			'items'  => [
				'_all'     => [ 'tag' => 'All',             'extra' => ' onClick=\'demo.selectAll()\''    ],
				'_none'    => [ 'tag' => 'None',            'extra' => ' onClick=\'demo.selectNone()\''   ],
				'_invert'  => [ 'tag' => 'Invert',          'extra' => ' onClick=\'demo.selectInvert()\'' ],
				'_cbox'    => [ 'tag' => 'Input: checkbox', 'extra' => ' onClick=\'demo.selectCbox()\''   ],
				'_radio'   => [ 'tag' => 'Input: radio',    'extra' => ' onClick=\'demo.selectRadio()\''  ],
				'_text'    => [ 'tag' => 'Input: text',     'extra' => ' onClick=\'demo.selectName( "text" )\''    ],
				'_select'  => [ 'tag' => 'Input: select',   'extra' => ' onClick=\'demo.selectName( "select" )\''  ],
				'_group'   => [ 'tag' => 'Input: Group',    'extra' => ' onClick=\'demo.selectName( "Group" )\''   ],
				'_grid'    => [ 'tag' => 'Input: Grid',     'extra' => ' onClick=\'demo.selectName( "Grid" )\''    ],
				'_defvals' => [ 'tag' => 'Input: DefVals',  'extra' => ' onClick=\'demo.selectName( "DefVals" )\'' ],
				'_toggle'  => [ 'tag' => 'Input: Toggle',   'extra' => ' onClick=\'demo.selectName( "Toggle" )\''  ],
			],
		], $_POST );
		/* @formatter:on */
		?>
		<form class="demo" id="control" method="post" action="?action=filter">
		<div id="selector" class="row mb-3">
			<div class="col-1">Output:</div>
			<div class="col-11" id="selector"><?php echo $Selector->getContents() ?></div>
			<div class="col-1">Filter:</div>
			<div class="col-11"><?php echo $Filter->getContents() ?></div>
		</div>
		<hr class="mb-3">
		<button type="submit" class="btn btn-primary">Apply</button>
	</form>
		<?php
	}

	// Reduce displayed fields to selected only
	$fields = array_intersect_key( $fields, $checked );
	?>

	<!-- Render admin table -->
	<form class="demo" method="post" action="?action=save"
		enctype="multipart/form-data">
		<pre>Request method: <?php echo $_SERVER['REQUEST_METHOD'] ?></pre>

		<?php if ( 'POST' == $_SERVER['REQUEST_METHOD'] ): ?>
			<pre>$options: <?php echo Input::escHtml( print_r( $options, true ) ) ?></pre>
		<?php endif; ?>

		<!-- Dont use Input() here! -->
		<hr class="mb-3">
		<div class="mb-3">
			<div class=" form-check form-check-inline">
				<input class="form-check-input" name="debug" id="debug" type="checkbox" <?php DEBUG && print('checked="checked"') ?>>
				<label class="form-check-label" for="debug">DEBUG mode</label>
			</div>
			<div class=" form-check form-check-inline">
				<input class="form-check-input" name="verbose" id="verbose" type="checkbox" <?php VERBOSE && print('checked="checked"') ?>>
				<label class="form-check-label" for="verbose">Show config</label>
			</div>
			<div class=" form-check form-check-inline">
				<input class="form-check-input" name="reset" id="reset" type="checkbox" >
				<label class="form-check-label" for="reset">Reset ALL</label>
			</div>
		</div>
		<button type="submit" class="btn btn-primary">Save</button>

		<?php
		$values = $ids = [];

		/* @formatter:off */
		$Table = new Inputs( [], [
			'table'  => [
				'class'     => 'table th-auto',
				'th1_title' => 'Config',
				'th2_title' => 'Output',
			],
			'fields' => VERBOSE ? $fields : null,
		]);
		/* @formatter:on */

		foreach ( $fields as $field ) {
			$Input = new Input( $field, $options );
			$Table->add( $Input );
			foreach ( Input::inputsAll( $Input->elements( true ), true ) as $Element ) {
				$ids[$Element->name()] = $Element->get( 'id' );
				$values[$Element->name()] = $Element->val();
			}
		}

		$Table->renderTable();
		?>

		<button type="submit" class="btn btn-primary">Save</button>
		<hr class="mb-3">

		<pre>Debug info:</pre>
		<div class="row">
			<div class="col">
				<pre>Input::val() <?php echo Input::escHtml( print_r( $values, true ) ) ?></pre>
			</div>
			<div class="col">
				<pre>Name2Id <?php echo Input::escHtml( print_r( $ids, true ) ) ?></pre>
			</div>
		</div>
	</form>

	<script src="demo.js"></script>
	<script>window.demo.ids = <?php echo json_encode( $ids ) ?></script>
</body>
</html>
