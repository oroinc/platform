import {EditorView} from '@codemirror/view';

const base00 = '#2e3440'; // black
const base01 = '#3b4252'; // dark grey
const base02 = '#434c5e';
const base03 = '#4c566a'; // grey

// Snow Storm
const base04 = '#d8dee9'; // grey
const base05 = '#e5e9f0'; // off white
const base06 = '#eceff4'; // white

// Frost
const base07 = 'red'; // moss green
const base08 = '#88c0d0'; // ice blue
const base09 = '#81a1c1'; // water blue
const base0A = '#5e81ac'; // deep blue

// Aurora
const base0b = '#bf616a'; // red
const base0C = 'green'; // orange
const base0D = '#ebcb8b'; // yellow
const base0E = '#a3be8c'; // green
const base0F = '#b48ead'; // purple

const invalid = '#d30102';
const darkBackground = base06;
const highlightBackground = darkBackground;
const background = '#ffffff';
const tooltipBackground = base05;
const selection = darkBackground;
const cursor = base01;

// The editor theme styles for Basic Light.

export const basicLightTheme = EditorView.theme(
    {
        '&': {
            color: base00,
            backgroundColor: background,
            height: '200px',
            border: '2px solid #d9d9da'
        },

        '.cm-content': {
            caretColor: cursor
        },

        '.cm-cursor, .cm-dropCursor': {
            borderLeftColor: cursor
        },

        '&.cm-focused .cm-selectionBackground, .cm-selectionBackground, .cm-content ::selection': {
            backgroundColor:
            selection
        },

        '.cm-panels': {
            backgroundColor: darkBackground,
            color: base03
        },
        '.cm-panels.cm-panels-top': {
            borderBottom: '2px solid black'
        },
        '.cm-panels.cm-panels-bottom': {
            borderTop: '2px solid black'
        },

        '.cm-searchMatch': {
            backgroundColor: '#72a1ff59',
            outline: `1px solid ${base03}`
        },
        '.cm-searchMatch.cm-searchMatch-selected': {
            backgroundColor: base05
        },

        '.cm-activeLine': {
            backgroundColor: highlightBackground
        },
        '.cm-selectionMatch': {
            backgroundColor: base05
        },

        '&.cm-focused .cm-matchingBracket, &.cm-focused .cm-nonmatchingBracket': {
            outline: `1px solid ${base03}`
        },

        '&.cm-focused .cm-matchingBracket': {
            backgroundColor: base06
        },

        '.cm-gutters': {
            backgroundColor: base06,
            color: base00,
            border: 'none'
        },

        '.cm-activeLineGutter': {
            backgroundColor: highlightBackground
        },

        '.cm-foldPlaceholder': {
            backgroundColor: 'transparent',
            border: 'none',
            color: '#ddd'
        },

        '.cm-tooltip': {
            border: 'none',
            backgroundColor: tooltipBackground
        },
        '.cm-tooltip .cm-tooltip-arrow:before': {
            borderTopColor: 'transparent',
            borderBottomColor: 'transparent'
        },
        '.cm-tooltip .cm-tooltip-arrow:after': {
            borderTopColor: tooltipBackground,
            borderBottomColor: tooltipBackground
        },
        '.cm-tooltip-autocomplete': {
            '& > ul > li[aria-selected]': {
                backgroundColor: highlightBackground,
                color: base03
            }
        }
    },
    {
        dark: false
    }
);
