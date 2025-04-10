import React, { useState } from "react";
import { Modal, Button, Table } from "antd";
import moment from "moment";
moment.locale('id');

export default function Index({ pasien }) {
    const [hasilObatData, setHasilObatData] = useState([]);
    const [loadingHasilObat, setLoadingHasilObat] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    const fetchHasilObat = async () => {
        setLoadingHasilObat(true);
        try {
            const response = await axios.get(
                route("rm.pasien-inap.get_all_obat", {
                    kode_reg: pasien.PRWINO_TRANSAKSI,
                })
            );
            setHasilObatData(response?.data?.data || []);
        } catch (error) {
            console.error("Error fetching hasil obat:", error);
        } finally {
            setLoadingHasilObat(false);
        }
    };

    const columns = [
        {
            title: "No Faktur",
            width: 100,
            render: (_, record) => (
                <div>
                    <span>{record.FHFJBUKTI_ID}</span> <br />
                    <span>{moment(record.FHFJDATE).format("DD-MMM-YYYY")}</span> <br />
                </div>
            ),
        },
        {
            title: "QTY | Obat",
            render: (_, record) => (
                <div>
                    <table
                        style={{ width: "100%", borderCollapse: "collapse" }}
                    >
                        <tbody>
                            {record?.items?.map((item, index) => (
                                <tr>
                                    <td style={{ width: "5%" }}>
                                        {parseInt(item.FDFJQTY)}
                                    </td>
                                    <td>{item.FDFJBRGN}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ),
        },
    ];

    return (
        <>
            <Button
                style={{ margin: 2 }}
                type="primary"
                onClick={() => {
                    setModalOpen(true);
                    if(hasilObatData.length === 0) {
                        fetchHasilObat();
                    }
                    return;
                }}
            >
                List Resep Obat
            </Button>

            {/* Modal untuk menampilkan detail hasil obat */}
            <Modal
                destroyOnClose
                title="Detail Hasil Obat"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={800}
            >
                <Table
                    loading={loadingHasilObat}
                    dataSource={hasilObatData}
                    columns={columns}
                    bordered
                    rowKey={(record, index) => index}
                    pagination={false}
                />
            </Modal>
        </>
    );
}
