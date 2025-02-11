import React from "react";
import { Modal } from "antd";
import moment from "moment";

import PasienRujukanDetailProcedureAdd from "./PasienRujukanDetailProcedureAdd";
import Button from "@/Components/Button";

export default function Index({
    procedure,
    loadingFetchProcedure,
    deleteProcedureId,
    showDeleteConfirm,
    selectedProcedure,
    setSelectedProcedure,
    fetchProcedure,
    pasien,
    isModalHapusProcedureOpen,
    handleCancelDelProcedure,
    currentProcedure,
    deleteProcedure,
}) {
    return (
        <>
            <div className="procedure-list">
                <div className="card bg-base-100 min-h-[200px]">
                    <div className="card-body">
                        <>
                            <div className="grid grid-cols-5 gap-5">
                                <div className="col-span-4">
                                    <strong>Procedure</strong>
                                </div>
                                <div className="col-span-1">
                                    <PasienRujukanDetailProcedureAdd
                                        pasien={pasien}
                                        className="float-end"
                                        refreshProcedure={fetchProcedure}
                                        selectedProcedure={selectedProcedure}
                                        setSelectedProcedure={
                                            setSelectedProcedure
                                        }
                                    />
                                </div>
                            </div>
                            {loadingFetchProcedure ? (
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
                                            <th style={{ width: "60%" }}>
                                                Tindakan
                                            </th>
                                            <th>Tanggal</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {procedure.map((item, index) => (
                                            <tr key={index}>
                                                <td>{index + 1}</td>
                                                <td>{item.MRTKD_TINDAKAN}</td>
                                                <td>{item.FMI9KETERANGAN}</td>
                                                <td>{moment(item.MRTTGL_TINDAKAN).format("DD/MM/YYYY")}</td>
                                                <td>
                                                    <Button
                                                        loading={
                                                            deleteProcedureId ===
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
                    </div>
                </div>
            </div>
            {/* Modal for Confirming Deletion */}
            <Modal
                title="Hapus Procedure"
                open={isModalHapusProcedureOpen}
                onOk={() =>
                    currentProcedure &&
                    deleteProcedure(
                        currentProcedure.ID,
                        currentProcedure.MRPKD_PENYAKIT
                    )
                }
                onCancel={handleCancelDelProcedure}
                okText="Ya"
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                <p>Apakah anda yakin ingin menghapus procedure ini?</p>
            </Modal>
        </>
    );
}
