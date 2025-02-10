import React, { useState, useEffect } from "react";
import Button from "@/Components/Button";
import axios from "axios";
import { notification } from "antd";

export default function DiagnosaAddBtn({
    disabled,
    pasien,
    refreshDiagnosa,
    selectedDiagnosa,
    setSelectedDiagnosa,
}) {
    const [diagnosaCari, setDiagnosaCari] = useState([]); // State to store search results
    const [loading, setLoading] = useState(false); // Loading state for fetching data
    const [loadingSaveDiag, setLoadingSaveDiag] = useState({}); // Loading state for each diagnosa
    const [query, setQuery] = useState(""); // State for input value
    const [page, setPage] = useState(1); // Current page number
    const [hasMore, setHasMore] = useState(true); // Flag to check if more data exists

    // useEffect(() => {
    //     setSelectedDiagnosa(selectedDiagnosaProps); // Panggil fungsi fetchDiagnosa saat komponen di-mount
    // }, []);

    // Function to handle input changes
    const handleInputChange = (event) => {
        const value = event.target.value;
        setQuery(value);

        // Reset states to start fresh search when query changes
        setDiagnosaCari([]); // Clear previous search results
        setPage(1); // Start from the first page
        setHasMore(true); // Ensure more data can be fetched
        fetchDiagnosa(value, 1); // Start fetching from the first page
    };

    // Function to fetch data from API
    const fetchDiagnosa = async (query, pageNumber) => {
        setLoading(true);
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.cari_penyakit"),
                {
                    query,
                    page: pageNumber,
                    selected_diagnosa: selectedDiagnosa, // Pass selected diagnosa to the server
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

    // Function to save kode diagnosa
    const saveDiagnosa = async (icd10_code) => {
        setLoadingSaveDiag((prev) => ({
            ...prev,
            [icd10_code]: true, // Set loading to true for the selected diagnosa
        }));
        try {
            const response = await axios.post(
                route("rm.pasien-rujukan.save_diagnosa"),
                {
                    icd10_code,
                    no_transaksikj: pasien.FRPNOTRANSAKSIKJ,
                    no_rm: pasien.FRPPASIEN_ID,
                    kd_unit: pasien.FRPUNIT,
                    tgl_masuk: pasien.FRPTGL,
                }
            );

            if (response?.data?.status === "ok") {
                notification.success({
                    placement: "bottomRight",
                    message: "Sukses!",
                    description: "Diagnosa berhasil ditambhakan.",
                });

                // Update the selectedDiagnosa state to include the newly saved diagnosa
                setSelectedDiagnosa((prevSelected) => [
                    ...prevSelected,
                    icd10_code,
                ]);

                // Refresh diagnosa list and reset loading for that diagnosa
                refreshDiagnosa();
                fetchDiagnosa(query, page);
            }
        } catch (error) {
            console.error("Error saving diagnosa:", error);
        } finally {
            setLoadingSaveDiag((prev) => ({
                ...prev,
                [icd10_code]: false, // Set loading to false for the selected diagnosa
            }));
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

    return (
        <>
            <button
                onClick={() => {
                    document.getElementById("modal_add_diadnosa").showModal();
                    fetchDiagnosa(query, 1);
                }}
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
                    {JSON.stringify(selectedDiagnosa)}
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
                                    <th style={{ width: "76%" }}>Penyakit</th>
                                    <th>Action</th>
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
                                                <Button
                                                    className="btn btn-xs btn-primary"
                                                    onClick={() =>
                                                        saveDiagnosa(
                                                            item.KD_PENYAKIT
                                                        )
                                                    }
                                                    loading={
                                                        loadingSaveDiag[
                                                            item.KD_PENYAKIT
                                                        ]
                                                    }
                                                    // Check if this diagnosa is loading
                                                    disabled={selectedDiagnosa.includes(
                                                        item.KD_PENYAKIT
                                                    )}
                                                >
                                                    Pilih
                                                </Button>
                                            </td>
                                        </tr>
                                    ))
                                )}
                                {loading && (
                                    <tr>
                                        <td colSpan="3" className="text-center">
                                            <span className="loading loading-md"></span>
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
