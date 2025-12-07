/**
 * A.7.4: Skip-Links für Keyboard-Navigation
 * 
 * Ermöglicht schnelle Navigation zu Hauptbereichen
 * ohne Tastatur-Navigation durch alle Elemente.
 */

import React from 'react';

interface SkipLink {
  href: string;
  label: string;
}

const defaultSkipLinks: SkipLink[] = [
  { href: '#main-content', label: 'Zum Hauptinhalt springen' },
  { href: '#navigation', label: 'Zur Navigation springen' },
  { href: '#footer', label: 'Zum Footer springen' },
];

interface SkipLinksProps {
  links?: SkipLink[];
}

export const SkipLinks: React.FC<SkipLinksProps> = ({ links = defaultSkipLinks }) => {
  return (
    <div className="skip-links">
      {links.map((link, index) => (
        <a
          key={index}
          href={link.href}
          className="skip-link"
          style={{
            position: 'absolute',
            top: '-40px',
            left: 0,
            background: '#000',
            color: '#fff',
            padding: '8px 16px',
            textDecoration: 'none',
            zIndex: 1000,
          }}
          onFocus={(e) => {
            e.currentTarget.style.top = '0';
          }}
          onBlur={(e) => {
            e.currentTarget.style.top = '-40px';
          }}
        >
          {link.label}
        </a>
      ))}
    </div>
  );
};









