import React, { useState } from "react";

// The SelectSearch component to be reused
const SelectSearch = ({
    options,
    value,
    className = "",
    placeholder = "Cari Opsi...",
    size = "sm", // Allow dynamic size, default is small (sm)
}) => {
    const [search, setSearch] = useState(value || "");
    const [selectedOption, setSelectedOption] = useState(null);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);

    // Filter options based on search input
    const filteredOptions = options.filter((option) =>
        option.toLowerCase().includes(search.toLowerCase())
    );

    // Handle keyboard navigation (Arrow Down, Arrow Up, Enter)
    const handleKeyDown = (e) => {
        if (e.key === "ArrowDown") {
            setHighlightedIndex((prevIndex) =>
                Math.min(filteredOptions.length - 1, prevIndex + 1)
            );
        } else if (e.key === "ArrowUp") {
            setHighlightedIndex((prevIndex) => Math.max(0, prevIndex - 1));
        } else if (e.key === "Enter" && highlightedIndex >= 0) {
            setSearch(filteredOptions[highlightedIndex]);
            setSelectedOption(filteredOptions[highlightedIndex]);
            setHighlightedIndex(-1); // Reset highlighted index after selecting
        }
    };

    // Determine Tailwind classes based on size prop
    const inputSizeClasses = {
        xs: "input-xs text-xs", // Extra small input and font size
        sm: "input-sm text-sm", // Small input and font size
        md: "input-md text-base", // Medium input and font size
        lg: "input-lg text-lg", // Large input and font size
    };

    return (
        <div className={`relative ${className}`}>
            <input
                type="text"
                value={search}
                onChange={(e) => {
                    setSearch(e.target.value);
                    setSelectedOption(null);
                }}
                onKeyDown={handleKeyDown}
                className={`input input-bordered w-full ${inputSizeClasses[size]}`} // Use dynamic size classes
                placeholder={placeholder}
                autoComplete="off"
            />
            {selectedOption === null && filteredOptions.length > 0 && (
                <ul className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md max-h-60 overflow-y-auto">
                    {filteredOptions.map((option, index) => (
                        <li
                            key={index}
                            className={`px-4 py-2 hover:bg-gray-200 cursor-pointer ${
                                highlightedIndex === index ? "bg-gray-300" : ""
                            }`}
                            onClick={() => {
                                setSearch(option);
                                setSelectedOption(option);
                                setHighlightedIndex(-1); // Reset highlighted index after selecting
                            }}
                            onMouseEnter={() => setHighlightedIndex(index)}
                        >
                            {option}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
};

export default SelectSearch;
