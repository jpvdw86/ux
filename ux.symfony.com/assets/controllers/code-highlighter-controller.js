import { Controller } from '@hotwired/stimulus';
import hljs from 'highlight.js/lib/core';
import hljs_javascript from 'highlight.js/lib/languages/javascript';
import hljs_php from 'highlight.js/lib/languages/php';
import hljs_xml from 'highlight.js/lib/languages/xml';
import hljs_twig from 'highlight.js/lib/languages/twig';
import hljs_yaml from 'highlight.js/lib/languages/yaml';
import hljs_diff from 'highlight.js/lib/languages/diff';

hljs.registerLanguage('javascript', hljs_javascript);
hljs.registerLanguage('php', hljs_php);
hljs.registerLanguage('twig', hljs_twig);
hljs.registerLanguage('yaml', hljs_yaml);
hljs.registerLanguage('diff', hljs_diff);
// xml is the language used for HTML
hljs.registerLanguage('xml', hljs_xml);


/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['codeBlock'];

    codeBlockTargetConnected() {
        this.codeBlockTargets.forEach(this.#highlightCodeBlock)
    }

    #highlightCodeBlock(codeBlock) {
        if (codeBlock.dataset.highlighted) {
            return;
        }
        hljs.highlightElement(codeBlock);
    }
}
