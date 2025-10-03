import '../../../scss/blocks/page-alert/editor.scss';
import { InnerBlocks, useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit(props) {
    const blockProps = useBlockProps({
        className: 'umw-edit-page-alert umw-custom-block'
    });

    return (
        <div {...blockProps}>
            <div className="umw-page-alert__inner">
                <div className="umw-page-alert__title">
                    <RichText
                        allowedFormats={['core/italic']}
                        value={props.attributes.title}
                        onChange={(input) => props.setAttributes({ title: input })}
                        inlineToolbar={true}
                        placeholder="Heading…"
                    />
                </div>

                <div className="umw-page-alert__content">
                    <InnerBlocks
                        allowedBlocks={[
                            'core/paragraph',
                            'core/list',
                        ]}
                    />
                </div>
            </div>
        </div>
    );
}