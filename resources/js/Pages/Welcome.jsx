import { Head, Link } from "@inertiajs/react";

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    const handleImageError = () => {
        document
            .getElementById("screenshot-container")
            ?.classList.add("!hidden");
        document.getElementById("docs-card")?.classList.add("!row-span-1");
        document
            .getElementById("docs-card-content")
            ?.classList.add("!flex-row");
        document.getElementById("background")?.classList.add("!hidden");
    };

    return (
        <>
            <div className="indicator">
                <span className="indicator-item badge badge-secondary"></span>
                <div className="bg-base-300 grid h-32 w-32 place-items-center">
                    content
                </div>
            </div>
        </>
    );
}
