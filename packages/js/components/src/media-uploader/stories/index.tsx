/**
 * External dependencies
 */
import React, { createElement } from 'react';
import { Card, CardBody, Modal, Notice } from '@wordpress/components';
import { MediaItem } from '@wordpress/media-utils';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { MediaUploader } from '../';
import { File } from '../types';

declare let Blob: {
	prototype: Blob;
	new (): Blob;
};

const MockMediaUpload = ( { onSelect, render } ) => {
	const [ isOpen, setOpen ] = useState( false );

	return (
		<>
			{ render( {
				open: () => setOpen( true ),
			} ) }
			{ isOpen && (
				<Modal
					title="Media Modal"
					onRequestClose={ () => setOpen( false ) }
				>
					<p>
						Use the default built-in{ ' ' }
						<code>MediaUploadComponent</code> prop to render the WP
						Media Modal.
					</p>
					{ Array( ...Array( 3 ) ).map( ( n, i ) => {
						return (
							<button
								key={ i }
								onClick={ () => {
									onSelect( {
										alt: 'Random',
										url: `https://picsum.photos/200?i=${ i }`,
									} );
									setOpen( false );
								} }
								style={ {
									marginRight: '16px',
								} }
							>
								<img
									src={ `https://picsum.photos/200?i=${ i }` }
									alt="Random"
									style={ {
										maxWidth: '100px',
									} }
								/>
							</button>
						);
					} ) }
				</Modal>
			) }
		</>
	);
};

const ImageGallery = ( { images }: { images: File[] } ) => {
	return (
		<div style={ { marginBottom: '16px' } }>
			{ images.map( ( image, index ) => {
				return (
					<img
						key={ index }
						alt={ image.alt }
						src={ image.url }
						style={ {
							maxWidth: '100px',
							marginRight: '16px',
						} }
					/>
				);
			} ) }
		</div>
	);
};

const readImage = ( file: Blob ) => {
	return new Promise< MediaItem >( ( resolve ) => {
		const fileReader = new FileReader();
		fileReader.onload = function ( event ) {
			const image = {
				alt: 'Temporary image',
				url: event?.target?.result,
			} as MediaItem;
			resolve( image );
		};
		fileReader.readAsDataURL( file );
	} );
};

const mockUploadMedia = async ( { filesList, onFileChange } ) => {
	const images = await Promise.all(
		filesList.map( ( file ) => readImage( file ) )
	);
	onFileChange( images );
};

export const Basic: React.FC = () => {
	const [ images, setImages ] = useState< File[] >( [] );

	return (
		<Card size="large">
			<CardBody>
				<ImageGallery images={ images } />
				<MediaUploader
					MediaUploadComponent={ MockMediaUpload }
					onSelect={ ( file ) => setImages( [ ...images, file ] ) }
					onError={ () => null }
					onUpload={ ( files ) =>
						setImages( [ ...images, ...files ] )
					}
					uploadMedia={ mockUploadMedia }
				/>
			</CardBody>
		</Card>
	);
};

export const DisabledDropZone: React.FC = () => {
	const [ images, setImages ] = useState< File[] >( [] );

	return (
		<Card size="large">
			<CardBody>
				<ImageGallery images={ images } />
				<MediaUploader
					hasDropZone={ false }
					label={ 'Click the button below to upload' }
					MediaUploadComponent={ MockMediaUpload }
					onSelect={ ( file ) => setImages( [ ...images, file ] ) }
					onError={ () => null }
					uploadMedia={ mockUploadMedia }
				/>
			</CardBody>
		</Card>
	);
};

export const MaxUploadFileSize: React.FC = () => {
	const [ error, setError ] = useState< string | null >( null );

	return (
		<Card size="large">
			<CardBody>
				{ error && (
					<Notice isDismissible={ false } status={ 'error' }>
						{ error }
					</Notice>
				) }

				<MediaUploader
					maxUploadFileSize={ 1000 }
					MediaUploadComponent={ MockMediaUpload }
					onSelect={ () => null }
					onError={ ( e ) => setError( e.message ) }
					onUpload={ () => null }
				/>
			</CardBody>
		</Card>
	);
};

export default {
	title: 'WooCommerce Admin/components/MediaUploader',
	component: Basic,
};
