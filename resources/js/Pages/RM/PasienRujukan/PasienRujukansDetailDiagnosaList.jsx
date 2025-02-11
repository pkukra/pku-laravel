import React from "react";
import PasienRujukansDetailDiagnosaAdd from "./PasienRujukansDetailDiagnosaAdd";
import Button from "@/Components/Button";

export default function Index({
    diagnosa,
    loadingFetchDiagnosa,
    deleteDiagnosaId,
    showDeleteConfirm,
    selectedDiagnosa,
    setSelectedDiagnosa,
    fetchDiagnosa,
    pasien,
}) {
    return (
        <div className="diagnosa-list">
            <div className="card bg-base-100">
                <div className="card-body">
                    <>
                        <div className="grid grid-cols-5 gap-5">
                            <div className="col-span-4">
                                <strong>Diagnosa</strong>
                            </div>
                            <div className="col-span-1">
                                <PasienRujukansDetailDiagnosaAdd
                                    pasien={pasien}
                                    className="float-end"
                                    refreshDiagnosa={fetchDiagnosa}
                                    selectedDiagnosa={selectedDiagnosa}
                                    setSelectedDiagnosa={setSelectedDiagnosa}
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
                                        <th style={{ width: "10%" }}>Kode</th>
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
                                                        showDeleteConfirm(item)
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
                </div>
            </div>
        </div>
    );
}
