import '../../../scss/blocks/page-alert/style.scss';
import { InnerBlocks } from '@wordpress/block-editor';

export default function Save(props) {
    return (
        <div className="umw-page-alert umw-custom-block">
            <div className="umw-page-alert__inner">
                <h2 className="umw-page-alert__title">
                    {props.attributes.title}
                </h2>

                <div className="umw-page-alert__content">
                    <InnerBlocks.Content />
                </div>
            </div>
        </div>
    );
}
