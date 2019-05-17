/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import CheckIcon from './check-icon';

/**
 * A stepper component to indicate progress in a set number of steps.
 */
class Stepper extends Component {
	render() {
        const { className, currentStep, steps } = this.props;
		const currentIndex = steps.findIndex( s => currentStep === s.key );
		const stepperClassName = classnames( 'woocommerce-stepper', className );

		return (
			<div className={ stepperClassName }>
                { steps.map( ( step, i ) => {
					const { key, label, isComplete } = step;
                    const stepClassName = classnames( 'woocommerce-stepper__step', {
                        'is-active': key === currentStep,
                        'is-complete': 'undefined' !== typeof isComplete ? isComplete : currentIndex > i,
                    } );

                    return (
						<Fragment key={ key } >
							<div
								className={ stepClassName }
							>
								<div className="woocommerce-stepper__step-icon">
									<span className="woocommerce-stepper__step-number">{ i + 1 }</span>
									<CheckIcon />
								</div>
								<span className="woocommerce-stepper_step-label">
									{ label }
								</span>
							</div>
							<div className="woocommerce-stepper__step-divider" />
						</Fragment>
                    );
                } ) }
			</div>
		);
	}
}

Stepper.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * The current step's key.
	 */
	currentStep: PropTypes.string.isRequired,
	/**
	 * An array of steps used.
	 */
	steps: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Key used to identify step.
			 */
			key: PropTypes.string.isRequired,
			/**
			 * Label displayed in stepper.
			 */
			label: PropTypes.string.isRequired,
			/**
			 * Optionally mark a step complete regardless of step index.
			 */
            isComplete: PropTypes.bool,
		} )
	).isRequired,
};

export default Stepper;
