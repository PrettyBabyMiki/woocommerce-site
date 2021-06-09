/**
 * External dependencies
 */
import classnames from 'classnames';
import { parse, stringify } from 'qs';
import PropTypes from 'prop-types';
import url from 'url';
import { isString } from 'lodash';
import md5 from 'md5';
import deprecated from '@wordpress/deprecated';

/**
 * Display a users Gravatar.
 *
 * @param {Object} props
 * @param {string} props.alt
 * @param {string} props.title
 * @param {string} props.size
 * @param {string|Object} props.user
 * @param {string} props.className
 * @return {Object} -
 */
const Gravatar = ( { alt, title, size, user, className } ) => {
	deprecated( 'Gravatar', {
		version: '8.0.0',
		plugin: 'WooCommerce',
		hint:
			'The Gravatar component will be removed in the next version of @woocommerce/components, please consider using another library to perform this function.',
	} );

	const classes = classnames( 'woocommerce-gravatar', className, {
		'is-placeholder': ! user,
	} );

	const getResizedImageURL = ( imageURL ) => {
		const parsedURL = url.parse( imageURL );
		const query = parse( parsedURL.query );

		query.s = size;
		query.d = 'mp';

		parsedURL.search = stringify( query );
		return url.format( parsedURL );
	};

	const getAvatarURLFromEmail = ( email ) => {
		return 'https://www.gravatar.com/avatar/' + md5( email );
	};

	const altText = alt || ( user && ( user.display_name || user.name ) ) || '';

	let avatarURL = 'https://www.gravatar.com/avatar/0?s=' + size + '&d=mp';
	if ( user ) {
		avatarURL = getResizedImageURL(
			isString( user )
				? getAvatarURLFromEmail( user )
				: user.avatar_URLs[ 96 ]
		);
	}

	return (
		<img
			alt={ altText }
			title={ title }
			className={ classes }
			src={ avatarURL }
			width={ size }
			height={ size }
		/>
	);
};

Gravatar.propTypes = {
	/**
	 * The address to hash for displaying a Gravatar. Can be an email address or WP-API user object.
	 */
	user: PropTypes.oneOfType( [ PropTypes.object, PropTypes.string ] ),
	/**
	 * Text to display as the image alt attribute.
	 */
	alt: PropTypes.string,
	/**
	 * Text to use for the image's title
	 */
	title: PropTypes.string,
	/**
	 * Default 60. The size of Gravatar to request.
	 */
	size: PropTypes.number,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
};

Gravatar.defaultProps = {
	size: 60,
};

export default Gravatar;
