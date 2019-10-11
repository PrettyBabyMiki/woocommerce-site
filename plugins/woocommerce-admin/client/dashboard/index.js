/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import './style.scss';
import CustomizableDashboard from './customizable';
import ProfileWizard from './profile-wizard';
import withSelect from 'wc-api/with-select';

class Dashboard extends Component {
	render() {
		const { path, profileItems, query } = this.props;

		if ( window.wcAdminFeatures.onboarding && ! profileItems.skipped && ! profileItems.completed ) {
			return <ProfileWizard query={ query } />;
		}

		if ( window.wcAdminFeatures[ 'analytics-dashboard/customizable' ] ) {
			return <CustomizableDashboard query={ query } path={ path } />;
		}

		return null;
	}
}

export default compose(
	withSelect( select => {
		if ( ! window.wcAdminFeatures.onboarding ) {
			return;
		}

		const { getProfileItems } = select( 'wc-api' );
		const profileItems = getProfileItems();

		return { profileItems };
	} )
)( Dashboard );
