<?php
namespace AIOSEO\Plugin\Pro\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Api as CommonApi;

/**
 * Api class for the admin.
 *
 * @since 4.0.0
 */
class Api extends CommonApi\Api {
	/**
	 * The routes we use in the rest API.
	 *
	 * @since 4.0.0
	 *
	 * @var array
	 */
	protected $proRoutes = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'POST' => [
			'activate'                                                  => [ 'callback' => [ 'License', 'activateLicense' ], 'access' => 'aioseo_general_settings' ],
			'deactivate'                                                => [ 'callback' => [ 'License', 'deactivateLicense' ], 'access' => 'aioseo_general_settings' ],
			'notification/local-business-organization-reminder'         => [ 'callback' => [ 'Notifications', 'localBusinessOrganizationReminder' ], 'access' => 'any' ],
			'notification/news-publication-name-reminder'               => [ 'callback' => [ 'Notifications', 'newsPublicationNameReminder' ], 'access' => 'any' ],
			'notification/v3-migration-local-business-number-reminder'  => [ 'callback' => [ 'Notifications', 'migrationLocalBusinessNumberReminder' ], 'access' => 'any' ],
			'notification/v3-migration-local-business-country-reminder' => [ 'callback' => [ 'Notifications', 'migrationLocalBusinessCountryReminder' ], 'access' => 'any' ],
			'notification/import-local-business-country-reminder'       => [ 'callback' => [ 'Notifications', 'importLocalBusinessCountryReminder' ], 'access' => 'any' ],
			'notification/import-local-business-type-reminder'          => [ 'callback' => [ 'Notifications', 'importLocalBusinessTypeReminder' ], 'access' => 'any' ],
			'notification/import-local-business-number-reminder'        => [ 'callback' => [ 'Notifications', 'importLocalBusinessNumberReminder' ], 'access' => 'any' ],
			'notification/import-local-business-fax-reminder'           => [ 'callback' => [ 'Notifications', 'importLocalBusinessFaxReminder' ], 'access' => 'any' ],
			'notification/import-local-business-currencies-reminder'    => [ 'callback' => [ 'Notifications', 'importLocalBusinessCurrenciesReminder' ], 'access' => 'any' ]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];

	/**
	 * Get all the routes to register.
	 *
	 * @since 4.0.0
	 *
	 * @return array An array of routes.
	 */
	protected function getRoutes() {
		$routes       = array_merge_recursive( parent::getRoutes(), $this->proRoutes );
		$loadedAddons = aioseo()->addons->getLoadedAddons();
		if ( ! empty( $loadedAddons ) ) {
			foreach ( $loadedAddons as $addon ) {
				if ( isset( $addon->api ) && method_exists( $addon->api, 'getRoutes' ) ) {
					$routes = array_replace_recursive(
						$addon->api->getRoutes(),
						$routes
					);
				}
			}
		}

		return $routes;
	}

	/**
	 * Pass any API class back to an addon to run a similar method.
	 *
	 * @since 4.1.0
	 *
	 * @param  \WP_Request  $request   The original request.
	 * @param  \WP_Response $response  An optional response.
	 * @param  string       $apiClass  The class to call.
	 * @param  string       $apiMethod The method to call on the class.
	 * @return mixed                  Anything the addon needs to return.
	 */
	public static function addonsApi( $request, $response, $apiClass, $apiMethod ) {
		$loadedAddons = aioseo()->addons->getLoadedAddons();
		if ( ! empty( $loadedAddons ) ) {
			foreach ( $loadedAddons as $addon ) {
				$class        = new \ReflectionClass( $addon );
				$addonClass   = $class->getNamespaceName() . $apiClass;
				$classExists  = class_exists( $addonClass );
				$methodExists = method_exists( $addonClass, $apiMethod );
				if ( $classExists && $methodExists ) {
					$response = call_user_func_array( [ $addonClass, $apiMethod ], [ $request, $response ] );
				}
			}
		}

		return $response;
	}

	/**
	 * Validates access from the routes array.
	 *
	 * @since 4.1.6
	 *
	 * @param  \WP_REST_Request $request The REST Request.
	 * @return bool                      True if validated, false if not.
	 */
	public function validateAccess( $request ) {
		$routeData = $this->getRouteData( $request );
		if ( empty( $routeData ) || empty( $routeData['access'] ) ) {
			return false;
		}

		switch ( $routeData['access'] ) {
			case 'everyone':
				// Any user is able to access the route.
				return true;
			case 'any':
				// The user has access if he has any of our capabilities.
				$user = wp_get_current_user();
				foreach ( $user->get_role_caps() as $capability => $enabled ) {
					if ( $enabled && preg_match( '/^aioseo_/', $capability ) ) {
						return true;
					}
				}

				return false;
			default:
				// The user has access if he has any of the required capabilities.
				if ( ! is_array( $routeData['access'] ) ) {
					$routeData['access'] = [ $routeData['access'] ];
				}

				foreach ( $routeData['access'] as $access ) {
					if ( current_user_can( $access ) ) {
						return true;
					}
				}

				return false;
		}
	}
}