/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { DropZoneProvider, DropZone, FormFileUpload } from '@wordpress/components';
import Gridicon from 'gridicons';
import { noop } from 'lodash';
import PropTypes from 'prop-types';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { Card, H, Spinner } from '@woocommerce/components';

class ThemeUploader extends Component {
	constructor() {
		super();

		this.state = {
			isUploading: false,
		};

		this.handleFilesUpload = this.handleFilesUpload.bind( this );
		this.handleFilesDrop = this.handleFilesDrop.bind( this );
	}

	handleFilesDrop( files ) {
		const file = files[ 0 ];
		this.uploadTheme( file );
	}

	handleFilesUpload( e ) {
		const file = e.target.files[ 0 ];
		this.uploadTheme( file );
	}

	uploadTheme( file ) {
		const { addNotice, onUploadComplete } = this.props;
		this.setState( { isUploading: true } );

		const body = new FormData();
		body.append( 'pluginzip', file );

		return apiFetch( { path: '/wc-admin/v1/themes', method: 'POST', body } )
			.then( response => {
				onUploadComplete( response );
				this.setState( { isUploading: false } );
				addNotice( { status: response.status, message: response.message } );
			} )
			.catch( error => {
				this.setState( { isUploading: false } );
				if ( error && error.message ) {
					addNotice( { status: 'error', message: error.message } );
				}
			} );
	}

	render() {
		const { className } = this.props;
		const { isUploading } = this.state;

		const classes = classnames( 'woocommerce-theme-uploader', className, {
			'is-uploading': isUploading,
		} );

		return (
			<Card className={ classes }>
				<DropZoneProvider>
					{ ! isUploading ? (
						<Fragment>
							<FormFileUpload accept=".zip" onChange={ this.handleFilesUpload }>
								<Gridicon icon="cloud-upload" />
								<H className="woocommerce-theme-uploader__title">
									{ __( 'Upload a theme', 'woocommerce-admin' ) }
								</H>
								<p>{ __( 'Drop a theme zip file here to upload', 'woocommerce-admin' ) }</p>
							</FormFileUpload>
							<DropZone
								label={ __( 'Drop your theme zip file here', 'woocommerce-admin' ) }
								onFilesDrop={ this.handleFilesDrop }
							/>
						</Fragment>
					) : (
						<Fragment>
							<Spinner />
							<H className="woocommerce-theme-uploader__title">
								{ __( 'Uploading theme', 'woocommerce-admin' ) }
							</H>
							<p>{ __( 'Your theme is being uploaded', 'woocommerce-admin' ) }</p>
						</Fragment>
					) }
				</DropZoneProvider>
			</Card>
		);
	}
}

ThemeUploader.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * Function called when an upload has finished.
	 */
	onUploadComplete: PropTypes.func,
};

ThemeUploader.defaultProps = {
	onUploadComplete: noop,
};

export default compose(
	withDispatch( dispatch => {
		const { addNotice } = dispatch( 'wc-admin' );
		return { addNotice };
	} )
)( ThemeUploader );
