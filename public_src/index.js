import { initCache } from './control/dynamic-sources/utils/cache';
import { registerDynamicSourceStore } from './control/dynamic-sources/store';
import PostSelector from './control/post-selector';
import ViewEditor from './control/view-editor';

function initializeDS() {
	initCache();
	registerDynamicSourceStore();
	new PostSelector();
	new ViewEditor();
}

initializeDS();

export { default as assureString } from './utils/assure-string';
export { default as withPostPreview } from './control/post-preview/with-post-preview';
export { fetchDynamicContent, fetchCache, searchPost, loadSourceFromCustomPost } from './control/dynamic-sources/utils/fetchData';
export { SaveWrapper } from './control/dynamic-sources/SaveWrapper';
export { EditWrapper } from './control/dynamic-sources/EditWrapper';
export { default as withToolsetDynamicField } from './with-dynamic-field/component';
export { default as getShortcodeOrStatic } from './with-dynamic-field/get-shortcode-or-static';
