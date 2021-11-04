const { __ } = wp.i18n;
const { compose } = wp.compose;
const { withSelect, withDispatch } = wp.data;

const { PluginDocumentSettingPanel } = wp.editPost;
const { ToggleControl, TextControl, PanelRow } = wp.components;

const Simple_Subtitles = ( { postMeta, setPostMeta } ) => {
	return(
		<PluginDocumentSettingPanel title={ __( 'Subtitle', 'simple-subtitle') } icon="subtitle" initialOpen="true">
			<PanelRow>
				<TextControl
					value={ postMeta._simple_subtitle }
					onChange={ ( value ) => setPostMeta( { _simple_subtitle: value } ) }
				/>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
}

export default compose( [
	withSelect( ( select ) => {
		return {
			postMeta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
			postType: select( 'core/editor' ).getCurrentPostType(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		return {
			setPostMeta( newMeta ) {
				dispatch( 'core/editor' ).editPost( { meta: newMeta } );
			}
		};
	} )
] )( Simple_Subtitles );
