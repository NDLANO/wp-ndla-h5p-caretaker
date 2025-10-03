<?php
/**
 * LocaleUtils
 *
 * @package ndla-h5p-caretaker
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
	 * Get the default language name for a given BCP 47 language code.
	 *
	 * @param string $bcp47 The BCP 47 language code.
	 *
	 * @return string|null The default language name or null if not found.
	 */
	public static function get_default_language_name( $bcp47 ) {
		$locale = self::get_complete_locale( $bcp47 );
		if ( ! isset( $locale ) ) {
			return null;
		}

		$language_names = array(
			'af_ZA'  => __( 'Afrikaans', 'ndla-h5p-caretaker' ),
			'ar_AE'  => __( 'Arabic', 'ndla-h5p-caretaker' ),
			'be_BY'  => __( 'Belarusian', 'ndla-h5p-caretaker' ),
			'bg_BG'  => __( 'Bulgarian', 'ndla-h5p-caretaker' ),
			'bn_BD'  => __( 'Bengali', 'ndla-h5p-caretaker' ),
			'bs_BA'  => __( 'Bosnian', 'ndla-h5p-caretaker' ),
			'ca_ES'  => __( 'Catalan', 'ndla-h5p-caretaker' ),
			'cs_CZ'  => __( 'Czech', 'ndla-h5p-caretaker' ),
			'cy_GB'  => __( 'Welsh', 'ndla-h5p-caretaker' ),
			'da_DK'  => __( 'Danish', 'ndla-h5p-caretaker' ),
			'de_DE'  => __( 'German', 'ndla-h5p-caretaker' ),
			'el_GR'  => __( 'Greek', 'ndla-h5p-caretaker' ),
			'en_US'  => __( 'English', 'ndla-h5p-caretaker' ),
			'eo'     => __( 'Esperanto', 'ndla-h5p-caretaker' ),
			'es_ES'  => __( 'Spanish', 'ndla-h5p-caretaker' ),
			'et_EE'  => __( 'Estonian', 'ndla-h5p-caretaker' ),
			'eu_ES'  => __( 'Basque', 'ndla-h5p-caretaker' ),
			'fa_IR'  => __( 'Persian', 'ndla-h5p-caretaker' ),
			'fi_FI'  => __( 'Finnish', 'ndla-h5p-caretaker' ),
			'fil_PH' => __( 'Filipino', 'ndla-h5p-caretaker' ),
			'fo_FO'  => __( 'Faroese', 'ndla-h5p-caretaker' ),
			'fr_FR'  => __( 'French', 'ndla-h5p-caretaker' ),
			'ga_IE'  => __( 'Irish', 'ndla-h5p-caretaker' ),
			'gl_ES'  => __( 'Galician', 'ndla-h5p-caretaker' ),
			'gu_IN'  => __( 'Gujarati', 'ndla-h5p-caretaker' ),
			'he_IL'  => __( 'Hebrew', 'ndla-h5p-caretaker' ),
			'hi_IN'  => __( 'Hindi', 'ndla-h5p-caretaker' ),
			'hr_HR'  => __( 'Croatian', 'ndla-h5p-caretaker' ),
			'hum_HU' => __( 'Hungarian', 'ndla-h5p-caretaker' ),
			'hy_AM'  => __( 'Armenian', 'ndla-h5p-caretaker' ),
			'id_ID'  => __( 'Indonesian', 'ndla-h5p-caretaker' ),
			'is_IS'  => __( 'Icelandic', 'ndla-h5p-caretaker' ),
			'it_IT'  => __( 'Italian', 'ndla-h5p-caretaker' ),
			'ja_JP'  => __( 'Japanese', 'ndla-h5p-caretaker' ),
			'ka_GE'  => __( 'Georgian', 'ndla-h5p-caretaker' ),
			'kk_KZ'  => __( 'Kazakh', 'ndla-h5p-caretaker' ),
			'km_KH'  => __( 'Khmer', 'ndla-h5p-caretaker' ),
			'kn_IN'  => __( 'Kannada', 'ndla-h5p-caretaker' ),
			'ko_KR'  => __( 'Korean', 'ndla-h5p-caretaker' ),
			'lt_LT'  => __( 'Lithuanian', 'ndla-h5p-caretaker' ),
			'lv_LV'  => __( 'Latvian', 'ndla-h5p-caretaker' ),
			'mk_MK'  => __( 'Macedonian', 'ndla-h5p-caretaker' ),
			'ml_IN'  => __( 'Malayalam', 'ndla-h5p-caretaker' ),
			'mn_MN'  => __( 'Mongolian', 'ndla-h5p-caretaker' ),
			'mr_IN'  => __( 'Marathi', 'ndla-h5p-caretaker' ),
			'ms_MY'  => __( 'Malay', 'ndla-h5p-caretaker' ),
			'mt_MT'  => __( 'Maltese', 'ndla-h5p-caretaker' ),
			'nb_NO'  => __( 'Norwegian Bokmål', 'ndla-h5p-caretaker' ),
			'ne_NP'  => __( 'Nepali', 'ndla-h5p-caretaker' ),
			'nl_NL'  => __( 'Dutch', 'ndla-h5p-caretaker' ),
			'nn_NO'  => __( 'Norwegian Nynorsk', 'ndla-h5p-caretaker' ),
			'pa_IN'  => __( 'Punjabi', 'ndla-h5p-caretaker' ),
			'pl_PL'  => __( 'Polish', 'ndla-h5p-caretaker' ),
			'pt_PT'  => __( 'Portuguese', 'ndla-h5p-caretaker' ),
			'ro_RO'  => __( 'Romanian', 'ndla-h5p-caretaker' ),
			'ru_RU'  => __( 'Russian', 'ndla-h5p-caretaker' ),
			'sk_SK'  => __( 'Slovak', 'ndla-h5p-caretaker' ),
			'sl_SI'  => __( 'Slovenian', 'ndla-h5p-caretaker' ),
			'sq_AL'  => __( 'Albanian', 'ndla-h5p-caretaker' ),
			'sr_RS'  => __( 'Serbian', 'ndla-h5p-caretaker' ),
			'sv_SE'  => __( 'Swedish', 'ndla-h5p-caretaker' ),
			'sw_KE'  => __( 'Swahili', 'ndla-h5p-caretaker' ),
			'ta_IN'  => __( 'Tamil', 'ndla-h5p-caretaker' ),
			'te_IN'  => __( 'Telugu', 'ndla-h5p-caretaker' ),
			'th_TH'  => __( 'Thai', 'ndla-h5p-caretaker' ),
			'tr_TR'  => __( 'Turkish', 'ndla-h5p-caretaker' ),
			'uk_UA'  => __( 'Ukrainian', 'ndla-h5p-caretaker' ),
			'ur_PK'  => __( 'Urdu', 'ndla-h5p-caretaker' ),
			'uz_UZ'  => __( 'Uzbek', 'ndla-h5p-caretaker' ),
			'vi_VN'  => __( 'Vietnamese', 'ndla-h5p-caretaker' ),
			'zh_CN'  => __( 'Chinese', 'ndla-h5p-caretaker' ),
			// Add more mappings if needed.
		);
		return $language_names[ $locale ] ?? null;
	}

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
			load_textdomain( 'ndla-h5p-caretaker', $translation_file );
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
