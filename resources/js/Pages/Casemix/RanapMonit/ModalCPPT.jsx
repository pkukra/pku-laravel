import React, { useState, useEffect } from "react";
import { Modal, Button, Table } from "antd";
import moment from "moment";

export default function Index({ kode_reg, pasien }) {
    const [CPPTData, setCPPTData] = useState([]);
    const [loadingCPPT, setLoadingCPPT] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    const fetchHasilCPPT = async (kode_reg) => {
        setLoadingCPPT(true);
        try {
            const response = await axios.get(
                route("rm.pasien-inap.get_list_cppt", {
                    kode_reg: kode_reg,
                })
            );
            setCPPTData(response?.data?.data || []);
        } catch (error) {
            console.error("Error fetching hasil cppt:", error);
        } finally {
            setLoadingCPPT(false);
        }
    };

    const columns = [
        {
            title: "Object",
            dataIndex: "object",
            key: "object",
            render: (_, record) => (
                <div>
                    <p>
                        {moment(record.mdd_date).format("DD MMMM YYYY")}{" "}
                        {moment(`${record.mdd_date} ${record.mdd_time}`).format(
                            "HH:mm:ss"
                        )}
                    </p>
                    <p>PPA: {record.FS_NM_PEG}</p>
                </div>
            ),
        },
        {
            title: "Detail",
            dataIndex: "detail",
            key: "detail",
            render: (_, record) => (
                <table style={{ width: "100%", borderCollapse: "collapse" }}>
                    <tbody>
                        {["S", "O", "A", "P"].map((label, index, arr) => (
                            <tr
                                key={index}
                                style={{
                                    borderBottom:
                                        index !== arr.length - 1
                                            ? "1px solid #ddd"
                                            : "none",
                                }}
                            >
                                <td
                                    style={{
                                        verticalAlign: "top",
                                        fontWeight: "bold",
                                        padding: "8px",
                                    }}
                                >
                                    ({label})
                                </td>
                                <td
                                    style={{
                                        verticalAlign: "top",
                                        padding: "8px",
                                    }}
                                    dangerouslySetInnerHTML={{
                                        __html: record[`FS_CPPT_${label}`],
                                    }}
                                ></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            ),
        },
    ];

    return (
        <>
            <Button
                style={{ margin: 2 }}
                type="primary"
                size="small"
                onClick={() => {
                    setModalOpen(true);
                    fetchHasilCPPT(kode_reg);
                }}
            >
                CPPT
            </Button>

            {/* Modal untuk menampilkan detail hasil cppt */}
            <Modal
                title="List CPPT"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={900}
            >
                <p>
                    No RM: <strong>{pasien?.FTKD_PASIEN} </strong> Nama Pasien:{" "}
                    <strong>{pasien?.NAMAPASIEN}</strong> No Transakasi:{" "}
                    <strong>{pasien?.FTNO_TRANSAKSI} </strong>
                </p>

                <div style={{ maxHeight: "70vh", overflowY: "auto" }}>
                    <Table
                        loading={loadingCPPT}
                        dataSource={CPPTData}
                        columns={columns}
                        bordered
                        rowKey={(record, index) => index}
                        pagination={false}
                    />
                </div>
            </Modal>
        </>
    );
}
