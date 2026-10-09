import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { PaginationLink } from '@/types';

/**
 * Los textos de Laravel ("&laquo; Previous", "Next &raquo;") llegan con
 * entidades HTML, por eso se traducen a flechas.
 */
function etiqueta(link: PaginationLink): string {
    if (link.label.includes('Previous')) {
        return '‹';
    }

    if (link.label.includes('Next')) {
        return '›';
    }

    return link.label;
}

export default function PaginationLinks({
    links,
}: {
    links: PaginationLink[];
}) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav
            className="flex flex-wrap items-center gap-1"
            aria-label="Paginación"
        >
            {links.map((link, index) =>
                link.url ? (
                    <Button
                        key={index}
                        asChild
                        size="sm"
                        variant={link.active ? 'default' : 'outline'}
                    >
                        <Link href={link.url} preserveScroll>
                            {etiqueta(link)}
                        </Link>
                    </Button>
                ) : (
                    <Button key={index} size="sm" variant="outline" disabled>
                        {etiqueta(link)}
                    </Button>
                ),
            )}
        </nav>
    );
}
