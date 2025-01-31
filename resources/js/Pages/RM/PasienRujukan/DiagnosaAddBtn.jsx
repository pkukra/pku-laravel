import React, { useState, useEffect } from "react";
import axios from "axios";

export default function DiagnosaAddBtn({ className = "", disabled }) {
    const [diagnosaCari, setDiagnosaCari] = useState([]); // State to store search results
    const [loading, setLoading] = useState(false); // Loading state
    const [query, setQuery] = useState(""); // State for input value
    const [page, setPage] = useState(1); // Current page number
    const [hasMore, setHasMore] = useState(true); // Flag to check if more data exists

    // Function to handle input changes
    const handleInputChange = (event) => {
        const value = event.target.value;
        setQuery(value);

        // If input has 1 or more characters, trigger the search after debounce
        if (value.length > 0) {
            // Reset states to start fresh search when query changes
            setDiagnosaCari([]); // Clear previous search results
            setPage(1); // Start from the first page
            setHasMore(true); // Ensure more data can be fetched
            fetchDiagnosa(value, 1); // Start fetching from the first page
        } else {
            setDiagnosaCari([]); // Clear results if input is less than 2 characters
            setHasMore(true); // Reset hasMore to true when query is cleared
        }
    };

    // Function to fetch data from API using axios
    const fetchDiagnosa = async (query, pageNumber) => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.cari_penyakit"),
                {
                    query,
                    page: pageNumber,
                }
            );
            // If no results, mark hasMore as false
            if (response.data.data.length === 0) {
                setHasMore(false);
            }
            // If it's the first page, reset the results, otherwise append new results
            if (pageNumber === 1) {
                setDiagnosaCari(response.data.data);
            } else {
                setDiagnosaCari((prev) => [...prev, ...response.data.data]);
            }
            setPage(pageNumber); // Update the current page
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
            setLoading(false);
        }
    };

    // Handle scroll event to load more data when the user reaches the bottom
    const handleScroll = (event) => {
        const bottom =
            event.target.scrollHeight ===
            event.target.scrollTop + event.target.clientHeight;
        if (bottom && hasMore && !loading) {
            fetchDiagnosa(query, page + 1); // Load the next page when bottom is reached
        }
    };

    useEffect(() => {
        if (query.length >= 2) {
            fetchDiagnosa(query, 1); // Start the search when the query is updated
        }
    }, [query]);

    return (
        <>
            <button
                onClick={() =>
                    document.getElementById("modal_add_diadnosa").showModal()
                }
                className="btn btn-xs btn-primary"
                disabled={disabled}
            >
                Add Diagnosa
            </button>

            <dialog id="modal_add_diadnosa" className="modal">
                <div className="modal-box w-10/11 max-w-3xl h-[550px]">
                    <form method="dialog">
                        <button className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">
                            ✕
                        </button>
                    </form>
                    <h3 className="font-bold text-lg">
                        Pencarian diagnosa/penyakit
                    </h3>
                    <input
                        type="text"
                        value={query}
                        onChange={handleInputChange}
                        placeholder="Ketik kode atau nama diagnosa/penyakit (min 2 karakter)..."
                        className="input input-bordered input-sm w-full mb-2 mt-2"
                    />
                    <div
                        className="overflow-x-auto max-h-[400px] mt-2"
                        onScroll={handleScroll}
                    >
                        <table
                            className="table table-xs table-zebra table-pin-cols table-pin-rows"
                            style={{ width: "100%" }}
                        >
                            <thead>
                                <tr>
                                    <th style={{ width: "15%" }}>Kode</th>
                                    <th>Penyakit</th>
                                    <th style={{ width: "15%" }}>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {diagnosaCari.length === 0 ? (
                                    <tr>
                                        <td colSpan="3" className="text-center">
                                            No results found
                                        </td>
                                    </tr>
                                ) : (
                                    diagnosaCari.map((item, index) => (
                                        <tr key={index}>
                                            <td>{item.KD_PENYAKIT}</td>
                                            <td>{item.PENYAKIT}</td>
                                            <td>
                                                <button className="btn btn-xs btn-primary">
                                                    Pilih
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                                {loading && (
                                    <tr>
                                        <td colSpan="3" className="text-center">
                                            <span className="loading loading-lg"></span>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
                <form method="dialog" className="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>
        </>
    );
}
