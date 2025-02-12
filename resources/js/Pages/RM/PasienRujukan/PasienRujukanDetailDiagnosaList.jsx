import React, { useState } from "react";
import { Modal } from "antd";

import PasienRujukanDetailDiagnosaAdd from "./PasienRujukanDetailDiagnosaAdd";
import Button from "@/Components/Button";
import SelectSearch from "@/Components/SelectSearch";

export default function Index({
    pasien,
    diagnosa,
    loadingFetchDiagnosa,
    deleteDiagnosaId,
    showDeleteConfirm,
    selectedDiagnosa,
    setSelectedDiagnosa,
    fetchDiagnosa,
    isModalHapusDiagnosaOpen,
    handleCancelDelDiagnosa,
    currentDiagnosa,
    deleteDiagnosa,
}) {
    const [selectedOption, setSelectedOption] = useState(null);

    const options = ["Opsi 1", "Opsi 2", "Opsi 3", "Opsi 4", "Opsi 5"];

    const handleOptionChange = (value) => {
        setSelectedOption(value);
    };

    return (
        <>
            <div className="diagnosa-list">
                <div className="card bg-base-100 min-h-[200px]">
                    <div className="card-body">
                        <>
                            <div className="grid grid-cols-5 gap-5">
                                <div className="col-span-4">
                                    <strong>Diagnosa</strong>
                                </div>
                                <div className="col-span-1">
                                    <PasienRujukanDetailDiagnosaAdd
                                        pasien={pasien}
                                        className="float-end"
                                        refreshDiagnosa={fetchDiagnosa}
                                        selectedDiagnosa={selectedDiagnosa}
                                        setSelectedDiagnosa={
                                            setSelectedDiagnosa
                                        }
                                    />
                                </div>
                            </div>
                            {loadingFetchDiagnosa ? (
                                <>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                    <div className="skeleton h-4 w-full"></div>
                                </>
                            ) : (
                                <table
                                    className="table table-xs"
                                    style={{ width: "100%" }}
                                >
                                    <thead>
                                        <tr>
                                            <th style={{ width: "5%" }}>NO</th>
                                            <th style={{ width: "10%" }}>
                                                Kode
                                            </th>
                                            <th style={{ width: "72%" }}>
                                                Penyakit
                                            </th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {diagnosa.map((item, index) => (
                                            <tr key={index}>
                                                <td>{index + 1}</td>
                                                <td>{item.MRPKD_PENYAKIT}</td>
                                                <td>{item.PENYAKIT}</td>
                                                <td>
                                                    <Button
                                                        loading={
                                                            deleteDiagnosaId ===
                                                            item.ID
                                                        }
                                                        className="btn btn-xs btn-outline btn-error"
                                                        onClick={() =>
                                                            showDeleteConfirm(
                                                                item
                                                            )
                                                        }
                                                    >
                                                        hapus
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </>
                        <SelectSearch
                            options={options}
                            value={selectedOption}
                            onChange={handleOptionChange}
                            className="max-w-xs sm:max-w-md lg:max-w-lg"
                            size="xs" // You can use "xs", "sm", or "lg" to adjust the size
                            placeholder="Cari Opsi..."
                        />
                        {selectedOption && (
                            <div className="mt-3">
                                <strong>Opsi yang dipilih: </strong>{" "}
                                {selectedOption}
                            </div>
                        )}
                    </div>
                </div>
            </div>
            {/* Modal for Confirming Deletion */}
            <Modal
                title="Hapus Diagnosa"
                open={isModalHapusDiagnosaOpen}
                onOk={() =>
                    currentDiagnosa &&
                    deleteDiagnosa(
                        currentDiagnosa.ID,
                        currentDiagnosa.MRPKD_PENYAKIT
                    )
                }
                onCancel={handleCancelDelDiagnosa}
                okText="Ya"
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                <p>Apakah anda yakin ingin menghapus diagnosa ini?</p>
            </Modal>
        </>
    );
}
