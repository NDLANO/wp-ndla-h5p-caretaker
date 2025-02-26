<?php
/**
 * LocaleUtils
 *
 * @package wp-ndla-h5p-caretaker
 */

namespace NDLAH5PCARETAKER;

/**
 * Class LocaleUtils
 *
 * This class provides utility functions for handling locales.
 * Deviates from the H5P Caretaker Server reference implementation, because of the customized WordPress environment.
 */
class LocaleUtils {
	/**
	 * The path to the locale files in a wWordPress plugin.
	 *
	 * @var string
	 */
	private static $locale_path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'languages';

	/**
	 * The default locale.
	 *
	 * @var string
	 */
	private static $default_locale = 'en_US';

	/**
	 * Get the complete (default) locale for a given language.
	 *
	 * @param string $language The language code.
	 *
	 * @return string The complete locale.
	 */
	public static function get_complete_locale( $language ) {
		// Define the mapping of short language codes to full locales.
		$locales = array(
			'af'  => 'af_ZA',
			'ar'  => 'ar_AE',
			'be'  => 'be_BY',
			'bg'  => 'bg_BG',
			'bn'  => 'bn_BD',
			'bs'  => 'bs_BA',
			'ca'  => 'ca_ES',
			'cs'  => 'cs_CZ',
			'cy'  => 'cy_GB',
			'da'  => 'da_DK',
			'de'  => 'de_DE',
			'el'  => 'el_GR',
			'en'  => 'en_US',
			'eo'  => 'eo',
			'es'  => 'es_ES',
			'et'  => 'et_EE',
			'eu'  => 'eu_ES',
			'fa'  => 'fa_IR',
			'fi'  => 'fi_FI',
			'fil' => 'fil_PH',
			'fo'  => 'fo_FO',
			'fr'  => 'fr_FR',
			'ga'  => 'ga_IE',
			'gl'  => 'gl_ES',
			'gu'  => 'gu_IN',
			'he'  => 'he_IL',
			'hi'  => 'hi_IN',
			'hr'  => 'hr_HR',
			'hu'  => 'hu_HU',
			'hy'  => 'hy_AM',
			'id'  => 'id_ID',
			'is'  => 'is_IS',
			'it'  => 'it_IT',
			'ja'  => 'ja_JP',
			'ka'  => 'ka_GE',
			'kk'  => 'kk_KZ',
			'km'  => 'km_KH',
			'kn'  => 'kn_IN',
			'ko'  => 'ko_KR',
			'lt'  => 'lt_LT',
			'lv'  => 'lv_LV',
			'mk'  => 'mk_MK',
			'ml'  => 'ml_IN',
			'mn'  => 'mn_MN',
			'mr'  => 'mr_IN',
			'ms'  => 'ms_MY',
			'mt'  => 'mt_MT',
			'nb'  => 'nb_NO',
			'ne'  => 'ne_NP',
			'nl'  => 'nl_NL',
			'nn'  => 'nn_NO',
			'pa'  => 'pa_IN',
			'pl'  => 'pl_PL',
			'pt'  => 'pt_PT',
			'ro'  => 'ro_RO',
			'ru'  => 'ru_RU',
			'sk'  => 'sk_SK',
			'sl'  => 'sl_SI',
			'sq'  => 'sq_AL',
			'sr'  => 'sr_RS',
			'sv'  => 'sv_SE',
			'sw'  => 'sw_KE',
			'ta'  => 'ta_IN',
			'te'  => 'te_IN',
			'th'  => 'th_TH',
			'tr'  => 'tr_TR',
			'uk'  => 'uk_UA',
			'ur'  => 'ur_PK',
			'uz'  => 'uz_UZ',
			'vi'  => 'vi_VN',
			'zh'  => 'zh_CN',
			// Add more mappings if needed.
		);

		// Validate the input.
		if ( preg_match( '/^[a-zA-Z]{2}_[a-zA-Z]{2}$/', $language ) ) {
			$split           = explode( '_', $language );
			$complete_locale = strtolower( $split[0] ) . '_' . strtoupper( $split[1] );
		} elseif ( preg_match( '/^[a-zA-Z]{2}|fil|FIL$/', $language ) ) {
			$language = strtolower( $language );

			if ( isset( $locales[ $language ] ) ) {
				$complete_locale = $locales[ $language ];
			} else {
				$complete_locale = $language . '_' . strtoupper( $language );
			}
		} else {
			return null;
		}

		return $complete_locale;
	}

	/**
	 * Get the available locales.
	 *
	 * @return array The available locales.
	 */
	public static function get_available_locales() {
		if ( ! is_dir( self::$locale_path ) ) {
			return array();
		}

		$found_locales = array_reduce(
			scandir( self::$locale_path ),
			function ( $locales, $entry ) {
				if ( preg_match( '/-([a-zA-Z]{2,3}|[a-zA-Z]{2,3}_\w{2})\.po/', $entry, $matches ) ) {
					$locales[] = $matches[1];
				}

				return $locales;
			},
			array()
		);

		return array_merge( array( self::$default_locale ), $found_locales );
	}

	/**
	 * Find a translation file for a given locale.
	 *
	 * @param string $locale The locale.
	 *
	 * @return string|null The path to the translation file or null if not found.
	 */
	public static function find_translation_file( $locale ) {
		$locale = self::get_complete_locale( $locale );
		if ( ! isset( $locale ) ) {
			return null;
		}

		return glob( self::$locale_path . DIRECTORY_SEPARATOR . 'NDLAH5PCARETAKER-' . $locale . '.mo' )[0] ?? null;
	}

	/**
	 * Request a translation for a given locale.
	 * If the locale is not available, the default locale is used.
	 *
	 * @param string $locale_requested The locale that could be served.
	 */
	public static function request_translation( $locale_requested ) {
		$locale = self::get_complete_locale( $locale_requested ?? self::$default_locale );
		if ( ! isset( $locale ) ) {
			return self::$default_locale;
		}

		$available_locales = self::get_available_locales();

		if ( ! in_array( $locale, $available_locales, true ) ) {
			return self::$default_locale;
		}

		$translation_file = self::find_translation_file( $locale );
		if ( ! empty( $translation_file ) ) {
			load_textdomain( 'wp-ndla-h5p-caretaker', $translation_file );
		} else {
			add_filter(
				'locale',
				function () {
					return self::$default_locale;
				}
			);
		}

		return $locale;
	}
}
