/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, createElement, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { pick } from 'lodash';
import { withDispatch } from '@wordpress/data';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import BusinessDetails from './steps/business-details';
import CartModal from '../components/cart-modal';
import Industry from './steps/industry';
import Plugins from './steps/plugins';
import ProductTypes from './steps/product-types';
import ProfileWizardHeader from './header';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import Start from './steps/start';
import StoreDetails from './steps/store-details';
import Theme from './steps/theme';
import withSelect from 'wc-api/with-select';
import { getProductIdsForCart } from 'dashboard/utils';
import './style.scss';

class ProfileWizard extends Component {
	constructor() {
		super( ...arguments );
		this.state = {
			showCartModal: false,
			cartRedirectUrl: null,
		};
		this.goToNextStep = this.goToNextStep.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { step: prevStep } = prevProps.query;
		const { step } = this.props.query;
		const {
			isError,
			isGetProfileItemsRequesting,
			createNotice,
		} = this.props;

		const isRequestError =
			! isGetProfileItemsRequesting && prevProps.isRequesting && isError;
		if ( isRequestError ) {
			createNotice(
				'error',
				__(
					'There was a problem finishing the profile wizard.',
					'woocommerce-admin'
				)
			);
		}

		if ( prevStep !== step ) {
			window.document.documentElement.scrollTop = 0;
		}
	}

	componentDidMount() {
		document.documentElement.classList.remove( 'wp-toolbar' );
		document.body.classList.add( 'woocommerce-onboarding' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );
	}

	componentWillUnmount() {
		const { cartRedirectUrl } = this.state;

		if ( cartRedirectUrl ) {
			document.body.classList.add( 'woocommerce-admin-is-loading' );
			window.location = cartRedirectUrl;
		}

		document.documentElement.classList.add( 'wp-toolbar' );
		document.body.classList.remove( 'woocommerce-onboarding' );
		document.body.classList.remove( 'woocommerce-profile-wizard__body' );
		document.body.classList.remove( 'woocommerce-admin-full-screen' );
	}

	getSteps() {
		const { profileItems } = this.props;
		const steps = [];

		steps.push( {
			key: 'start',
			container: Start,
		} );
		steps.push( {
			key: 'plugins',
			container: Plugins,
			isComplete:
				profileItems.hasOwnProperty( 'plugins' ) &&
				profileItems.plugins !== null,
		} );
		steps.push( {
			key: 'store-details',
			container: StoreDetails,
			label: __( 'Store Details', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'setup_client' ) &&
				profileItems.setup_client !== null,
		} );
		steps.push( {
			key: 'industry',
			container: Industry,
			label: __( 'Industry', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'industry' ) &&
				profileItems.industry !== null,
		} );
		steps.push( {
			key: 'product-types',
			container: ProductTypes,
			label: __( 'Product Types', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'product_types' ) &&
				profileItems.product_types !== null,
		} );
		steps.push( {
			key: 'business-details',
			container: BusinessDetails,
			label: __( 'Business Details', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'product_count' ) &&
				profileItems.product_count !== null,
		} );
		steps.push( {
			key: 'theme',
			container: Theme,
			label: __( 'Theme', 'woocommerce-admin' ),
			isComplete:
				profileItems.hasOwnProperty( 'theme' ) &&
				profileItems.theme !== null,
		} );
		return steps;
	}

	getCurrentStep() {
		const { step } = this.props.query;
		const currentStep = this.getSteps().find( ( s ) => s.key === step );

		if ( ! currentStep ) {
			return this.getSteps()[ 0 ];
		}

		return currentStep;
	}

	async goToNextStep() {
		const currentStep = this.getCurrentStep();
		const currentStepIndex = this.getSteps().findIndex(
			( s ) => s.key === currentStep.key
		);
		const nextStep = this.getSteps()[ currentStepIndex + 1 ];

		if ( typeof nextStep === 'undefined' ) {
			this.possiblyShowCart();
			return;
		}

		return updateQueryString( { step: nextStep.key } );
	}

	possiblyShowCart() {
		const { profileItems } = this.props;

		// @todo This should also send profile information to woocommerce.com.

		const productIds = getProductIdsForCart( profileItems );
		if ( productIds.length ) {
			this.setState( { showCartModal: true } );
		} else {
			this.completeProfiler();
		}
	}

	completeProfiler() {
		const { notes, updateNote, updateProfileItems } = this.props;
		updateProfileItems( { completed: true } );

		const profilerNote = notes.find(
			( note ) => note.name === 'wc-admin-onboarding-profiler-reminder'
		);
		if ( profilerNote ) {
			updateNote( profilerNote.id, { status: 'actioned' } );
		}
	}

	markCompleteAndPurchase( cartRedirectUrl ) {
		this.setState( { cartRedirectUrl } );
		this.completeProfiler();
	}

	render() {
		const { query } = this.props;
		const { showCartModal } = this.state;
		const step = this.getCurrentStep();

		const container = createElement( step.container, {
			query,
			step,
			goToNextStep: this.goToNextStep,
		} );
		const steps = this.getSteps().map( ( _step ) =>
			pick( _step, [ 'key', 'label', 'isComplete' ] )
		);

		return (
			<Fragment>
				{ showCartModal && (
					<CartModal
						onClose={ () =>
							this.setState( { showCartModal: false } )
						}
						onClickPurchaseNow={ ( cartRedirectUrl ) =>
							this.markCompleteAndPurchase( cartRedirectUrl )
						}
						onClickPurchaseLater={ () => this.completeProfiler() }
					/>
				) }
				<ProfileWizardHeader currentStep={ step.key } steps={ steps } />
				<div className="woocommerce-profile-wizard__container">
					{ container }
				</div>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const { getNotes, getProfileItems, getProfileItemsError } = select(
			'wc-api'
		);

		const notesQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'update',
			status: 'unactioned',
		};
		const notes = getNotes( notesQuery );

		return {
			isError: Boolean( getProfileItemsError() ),
			notes,
			profileItems: getProfileItems(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateNote, updateProfileItems } = dispatch( 'wc-api' );
		const { createNotice } = dispatch( 'core/notices' );

		return {
			createNotice,
			updateNote,
			updateProfileItems,
		};
	} )
)( ProfileWizard );
